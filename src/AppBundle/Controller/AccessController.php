<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Respondent;
use AppBundle\Form\KeyType;
use AppBundle\Form\RespondentType;
use AppBundle\Survey\DomainIdentifier;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Validator\Constraints as Assert;

class AccessController extends Controller
{
    /**
     * @Route(
     *     "/{key}",
     *     name="homepage",
     *     defaults={
     *         "key": "",
     *     },
     *     requirements={
     *         "key": ".{8}"
     *     }
     * )
     */
    public function indexAction(Request $request, $key = '')
    {
        $keyForm = $this->createForm(KeyType::class);
        if ($errorMessage = $this->get('session')->get('lastKeyFormError')) {
            $keyForm['key']->addError(new FormError($errorMessage));
            $this->get('session')->set('lastKeyFormError', null);
        }
        if ($key) {
            $keyForm['key']->setData($key);
        }
        $keyForm->handleRequest($request);
        $em = $this->getDoctrine()->getManager();
        if ($keyForm->isSubmitted() && $keyForm->isValid()) {
            /** @var Respondent $respondent */
            $respondent = $this->getDoctrine()->getRepository(Respondent::class)->findOneBy(
                ['key' => $keyForm->getData()['key']]
            );
            if (!$respondent) {
                $this->get('session')->set('lastKeyFormError', 'Cette clé n’a pas été trouvée.');

                return $this->redirectToRoute('homepage');
            } elseif ($respondent->isFinished()) {
                $this->get('session')->set(
                    'lastKeyFormError',
                    'Vous avez déjà completé le formulaire. Vous ne pouvez pas en modifier la saisie.'
                );

                return $this->redirectToRoute('homepage');
            } else {
                $this->get('session')->set('currentRespondentKey', $respondent->getKey());
                $respondent->setLastConnectionDate(new \DateTime());
                $respondent->setRevivedCount(0);
                $em->persist($respondent);
                $em->flush();

                if ($respondent->getIsManager() === null) {
                    return $this->redirectToRoute('is_manager');
                } else {
                    return $this->redirectToRoute('survey');
                }
            }
        }

        $respondent = new Respondent();
        $registerForm = $this->createForm(RespondentType::class, $respondent, ['attr' => ['source' => 'homepage']]);
        $registerForm->handleRequest($request);
        if ($registerForm->isSubmitted() && $registerForm->isValid()) {
            $respondent->setSource(Respondent::SOURCE_HOME);
            $respondent->setDomain($this->get(DomainIdentifier::class)->getIdentifier());
            $em->persist($respondent);
            $em->flush();

            if ($request->getHost() == 'teeprecherche2018.teep.fr') {
                $emailDest = 'lucie.prunes@etu.univ-nantes.fr';
            } else {
                $emailDest = 'kristina@beauvivre.fr';
            }
            /** @var \Swift_Mailer $mailer */
            $mailer = $this->get(\Swift_Mailer::class);
            /** @var \Swift_Message $message */
            $message = $mailer->createMessage();
            $message->setSubject('Demande teep research');
            $message->setTo($emailDest);
            $message->setBody(
                sprintf(
                    'Une nouvelle personne a fait une demande pour répondre au formulaire teep research. Son email : %s',
                    $respondent->getEmail()
                )
            );
            $mailer->send($message);

            $this->addFlash('success', 'La clé d’activation vous sera prochainement transmise par courriel');

            return $this->redirectToRoute('homepage');
        }

        return $this->render('index.html.twig', [
            'dataFound' => $this->get('session')->get('data') ? true : false,
            'keyForm' => $keyForm->createView(),
            'registerForm' => $registerForm->createView(),
        ]);
    }

    /**
     * @Route("/ie-no-more",
     *     name="ie-no-more"
     * )
     */
    public function ieAction()
    {
        return $this->render('ie-no-more.html.twig', [
            'ie' => true,
        ]);
    }
}
