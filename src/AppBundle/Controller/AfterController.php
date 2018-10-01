<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Respondent;
use AppBundle\Form\GetFeedbackType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class AfterController extends Controller
{
    /**
     * @Route("/merci", name="thanks")
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function merciAction(Request $request)
    {
        if ($this->getParameter('mode') === 'anonymous') {
            return $this->redirectToRoute('anonymous_thanks');
        }

        $form = null;
        if ($respondentId = $this->get('session')->get('respondentId')) {
            $repository = $this->getDoctrine()->getRepository(Respondent::class);
            $respondent = $repository->find($respondentId);
            $manager = $respondent->getManager();
            $form = $this->createForm(GetFeedbackType::class, $respondent, ['attr' => ['source' => Respondent::SOURCE_AFTER]]);
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                $this->get('session')->set('respondentId', null);
                $em = $this->getDoctrine()->getManager();
                foreach ($form['colleagues']->getData() as $colleague) {
                    if ($colleague) {
                        $existing = $repository->findOneBy(['key' => $colleague->getKey()]);
                        if (!$existing) {
                            $em->persist($colleague);
                        }
                    }
                }
                foreach ($form['subordinates']->getData() as $subordinate) {
                    if ($subordinate && $subordinate->getEmail()) {
                        $existing = $repository->findOneBy(['key' => $subordinate->getKey()]);
                        if (!$existing) {
                            $respondent->addSubordinate($subordinate);
                        }
                    }
                }
                if (!$manager && $respondent->getManager()) {
                    if ($existing = $repository->findOneBy(['key' => $respondent->getManager()->getKey()])) {
                        $respondent->unsetManager();
                        $respondent->setManager($existing);
                    }
                }
                $em->persist($respondent);
                $em->flush();

                return $this->redirectToRoute('thanks');
            }
        }

        return $this->render(
            'merci.html.twig',
            [
                'form' => $form ? $form->createView() : null,
            ]
        );
    }

    /**
     * @Route("/remerciements", name="anonymous_thanks")
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function merci2Action(Request $request)
    {
        if ($this->getParameter('mode') !== 'anonymous') {
            return $this->redirectToRoute('thanks');
        }

        $form = null;
        if ($respondentId = $this->get('session')->get('respondentId')) {
            $repository = $this->getDoctrine()->getRepository(Respondent::class);
            $respondent = $repository->find($respondentId);
            $form = $this->createForm(GetFeedbackType::class, $respondent, ['attr' => ['source' => Respondent::SOURCE_AFTER]]);
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                $this->get('session')->set('respondentId', null);
                $em = $this->getDoctrine()->getManager();
                $em->persist($respondent);
                $em->flush();

                return $this->redirectToRoute('thanks');
            }
        }

        return $this->render(
            'merci-anonymous.html.twig',
            [
                'form' => $form ? $form->createView() : null,
            ]
        );
    }
}
