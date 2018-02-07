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
        $form = null;
        if ($respondentId = $this->get('session')->get('respondentId')) {
            $respondent = $this->getDoctrine()->getRepository(Respondent::class)->find($respondentId);
            $form = $this->createForm(GetFeedbackType::class, $respondent);
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
//                $this->get('session')->set('respondentId', null);
                $em = $this->getDoctrine()->getManager();
                foreach ($form['colleagues']->getData() as $colleague) {
                    $em->persist($colleague);
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
}
