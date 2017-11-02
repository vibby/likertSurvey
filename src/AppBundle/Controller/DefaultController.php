<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\HttpFoundation\Request;

use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Validator\Constraints as Assert;

class DefaultController extends Controller
{
    /**
     * @Route("/", name="homepage")
     */
    public function indexAction(Request $request)
    {
        return $this->render('index.html.twig', [
            'dataFound' => $this->get('session')->get('data') ? true : false,
        ]);
    }


    /**
     * @Route("/ie-no-more")
     */
    public function ieAction()
    {
        return $this->render('ie-no-more.html.twig', [
            'ie' => true,
        ]);
    }

    /**
     * @Route("/merci", name="thanks")
     */
    public function merciAction()
    {
        return $this->render('merci.html.twig');
    }

    /**
     * @Route("/resultat", name="results")
     */
    public function resultAction()
    {
        $responses = array();
        $path = $this->container->getParameter('kernel.root_dir'). '/../app/Responses/';
        if (($handle = fopen($path . '/_toutes.csv', "r")) !== FALSE) {
            while (($line = fgetcsv($handle, 1000, ";")) !== FALSE) {
                $responses[] = $line;
            }
            fclose($handle);
        }

        $likertQuestions = $this->container->getParameter('likert_questions');
        $questionsFlat = array();
        foreach ($likertQuestions as $page => $questions) {
            foreach ($questions as $question) {
                // $question['scale'] = $likertScales[$question['scale']];
                $questionsFlat[] = $question;
            }
        }
        $questionsFlat = array_merge(
            array(
                array('label'=>'date_debut'),
                array('label'=>'date_fin'),
            ),
            $questionsFlat,
            array(
                array('label'=>'age'),
                array('label'=>'Sexe'),
                array('label'=>'Situation_famille'),
                array('label'=>'Nombre_enfants_a_charge'),
                array('label'=>'Profession'),
                array('label'=>'Secteur'),
                array('label'=>'Intitule_poste'),
                array('label'=>'Heures_travail_semaine'),
                array('label'=>'Heures_travail_mois'),
                array('label'=>'Satisfaction_salaire'),
                array('label'=>'Duree_poste'),
                array('label'=>'Duree_entreprise'),
                array('label'=>'Societe'),
                array('label'=>'Domain'),
                array('label'=>'Domain_other'),
                array('label'=>'Nombre_salaries_etablissement'),
                array('label'=>'Nombre_salaries_entreprise'),
            )
        );

        $inversedResponses = array();
        foreach ($responses as $x => $line) {
            foreach ($line as $y => $item) {
                $inversedResponses[$y][$x] = $item;
            }
        }

        $iResponse = 0;
        $data = array();
        foreach ($questionsFlat as &$question) {
            if (array_key_exists($iResponse, $inversedResponses)) {
                $data[] = array_merge(array($question['label']),$inversedResponses[$iResponse]);
                $iResponse++;
            } else {
                $data[] = array($question['label']);
            }
        }

        return $this->render('data.html.twig', [
            'responses' => $data,
        ]);
    }

    /**
     * @Route("/questionnaire/{idPage}", name="survey")
     */
    public function questionnaireAction(Request $request, $idPage = 1)
    {
        $likertScales = $this->container->getParameter('likert_scales');
        $likertQuestions = $this->container->getParameter('likert_questions');

        $sectors = array(
            'Public',
            'Privé',
            'Parapublic',
            );
        $jobs = array(
            "Agriculteurs exploitants",
            "Artisans",
            "Commerçants et assimilés",
            "Chefs d'entreprise de plus de 10 salariés ou plus",
            "Professions libérales et assimilés",
            "Cadres de la fonction publique, professions intellectuelles et artistiques",
            "Cadres d'entreprise",
            "Professions intermédiaires de l'enseignement, de la santé, de la fonction publique et assimilés",
            "Professions intermédiaires administratives et commerciales des entreprises",
            "Techniciens",
            "Contremaîtres, agents de maîtrise",
            "Employés de la fonction publique",
            "Employés administratifs d'entreprise",
            "Employés de commerce",
            "Personnels de services directs aux particuliers",
            "Ouvriers qualifiés",
            "Ouvriers non qualifiés",
            "Ouvriers agricoles"
            );
        $domains = array(
            "Agriculture",
            "Industrie",
            "Électricité, gaz et eau",
            "Construction",
            "Commerce",
            "Hôtels et restaurants",
            "Transport",
            "Communication",
            "Finances, banques et assurances",
            "Immobilier",
            "Administration publique",
            "Education - Enseignement",
            "Social - Aide aux personnes",
            "Santé",
            "Informatique et nouvelles technologies",
            "Autre, préciser ci-dessous",
            );

        $sessionData = $this->get('session')->get('data');
        if(!$sessionData) {
            $sessionData = array('dateDebut' => time());
        }

        $idPage = 0;
        $found = false;
        do {
            $idPage++;
            if (!array_key_exists('page'. $idPage, $likertQuestions))
                $found = true;
            else foreach ($likertQuestions['page'. $idPage] as $qKey => $likertQuestion) {
                if (!array_key_exists('page'. $idPage.'_item'.$qKey, $sessionData)) {
                    $found = true;
                }
            }
        } while (array_key_exists('page'. $idPage, $likertQuestions) && !$found);

        $formBuilder = $this->createFormBuilder($sessionData);
        $isLastPage = false;

        if ($idPage <= count($likertQuestions)) {
            foreach( $likertQuestions['page'. $idPage] as $qKey => $likertQuestion) {
                $choices = $this->getChoicesFromScale($likertQuestion);
                $formBuilder->add( 'page'.$idPage.'_item'.$qKey , Type\ChoiceType::class, array(
                    'choices' => $choices ? $choices : [],
                    'expanded' => true,
                    'multiple' => false,
                    'constraints' => $choices ? new Assert\Choice(array_values($choices)) : null,
                    'attr' => array(
                        'class' => $likertQuestion['type'],
                    ),
                    'label' => $likertQuestion['label'],
                ));
            }
            // dump($formBuilder);die;
        } else {
            $isLastPage = true;
            $formBuilder
                ->add( 'age', Type\IntegerType::class, array(
                    'label' => "Votre age",
                    'required' => true,
                ))
                ->add( 'Sexe', Type\ChoiceType::class, array(
                    'placeholder' => '-sélectionner-',
                    'choices' => array_flip(array('Homme','Femme')) ,
                    'expanded' => true,
                    'multiple' => false,
                    'constraints' => new Assert\Choice(array(0,1)),
                    'label' => "Sexe :",
                    'required' => true,
                ))
                ->add( 'Situation_famille', Type\ChoiceType::class, array(
                    'placeholder' => '-sélectionner-',
                    'choices' => array_flip(array('Seul','En couple')) ,
                    'expanded' => true,
                    'multiple' => false,
                    'constraints' => new Assert\Choice(array(0,1)),
                    'label' => "Situation familiale :",
                    'required' => true,
                ))
                ->add( 'Nombre_enfants_a_charge', Type\ChoiceType::class, array(
                    'constraints' => new Assert\Type('Integer', 'Cette valeur doit être un nombre entier'),
                    'label' => "Nombre d'enfants ou de personnes à votre charge :",
                    'placeholder' => '-sélectionner-',
                    'choices' => range(0, 10)
                ))
                ->add( 'Profession', Type\ChoiceType::class, array(
                    'placeholder' => '-sélectionner-',
                    'choices' => array_flip($jobs) ,
                    'expanded' => false,
                    'multiple' => false,
                    'constraints' => new Assert\Choice(array_keys($jobs)),
                    'label' => "Quelle est votre profession ?",
                    'required' => true,
                ))
                ->add( 'Secteur', Type\ChoiceType::class, array(
                    'placeholder' => '-sélectionner-',
                    'choices' => array_flip($sectors) ,
                    'expanded' => false,
                    'multiple' => false,
                    'constraints' => new Assert\Choice(array_keys($sectors)),
                    'label' => "Quel est le type de secteur de votre entreprise ?",
                    'required' => true,
                ))
                ->add( 'Intitule_poste', Type\TextType::class, array(
                    'label' => "Quel est l'intitulé exact de votre poste actuel ?",
                    'required' => true,
                ))
                ->add( 'Heures_travail_semaine', Type\IntegerType::class, array(
                    'label' => "Combien d'heures par semaine travaillez-vous ?",
                    'required' => true,
                ))
                ->add( 'Heures_travail_mois', Type\IntegerType::class, array(
                    'label' => "Combien d'heures supplémentaires effectuez-vous par mois, environ ?",
                    'required' => true,
                ))
                ->add( 'travaillez_vous', Type\ChoiceType::class, array(
                    'placeholder' => '-sélectionner-',
                    'label' => 'Travaillez-vous',
                    'required' => true,
                    'choices' => array_flip(array(
                        'de jour',
                        'de nuit',
                        'en 2/8',
                        'en 3/8',
                        'autre (précisez)',
                    ))
                ))
                ->add('travaillez_vous_other', Type\TextType::class, array(
                    'label' => 'Si autre, précisez',
                    'required' => false,
                ))
                ->add( 'Satisfaction_salaire', Type\ChoiceType::class, array(
                    'label' => "Êtes-vous satisfait(e) de votre salaire net mensuel ?",
                    'required' => true,
                    'expanded' => true,
                    'choices' => array_flip(array(
                        'oui',
                        'non',
                    ))
                ))
            ;

            /** @var FormFactory $formFactory */
            $formFactory = $this->get('form.factory');
            $formBuilder2 = $formFactory
                ->createNamedBuilder('Duree_poste', Type\FormType::class, array(
                    'label' => "Depuis quand travaillez-vous à votre poste actuel (années et mois) ?",
                    'required' => true,
                    ))
                ->add('Duree_poste_ans', Type\ChoiceType::class, array(
                    'label' => ' ',
                    'required' => true,
                    'placeholder' => '-années-',
                    'choices' => range(0, 45)
                    ))
                ->add('Duree_poste_mois', Type\ChoiceType::class, array(
                    'label' => ' ',
                    'required' => true,
                    'placeholder' => '-mois-',
                    'choices' => range(0, 11)
                    ))
            ;
            $formBuilder3 = $this->get('form.factory')
                ->createNamedBuilder('Duree_societe', Type\FormType::class, array(
                    'label' => "Depuis quand travaillez-vous dans votre entreprise actuel (années et mois) ?",
                    'required' => true,
                    ))
                ->add('Duree_societe_ans', Type\ChoiceType::class, array(
                    'label' => ' ',
                    'required' => true,
                    'placeholder' => '-années-',
                    'choices' => range(0, 45)
                    ))
                ->add('Duree_societe_mois', Type\ChoiceType::class, array(
                    'label' => ' ',
                    'required' => true,
                    'placeholder' => '-mois-',
                    'choices' => range(0, 11)
                    ))
            ;

            $formBuilder
                ->add($formBuilder2, null, array(
                    'required' => true,
                    ))
                ->add($formBuilder3, null, array(
                    'required' => true,
                    ))
                ->add( 'Societe', Type\TextType::class, array(
                    'label' => "Nom de votre entreprise (facultatif) :",
                    'required' => false ,
                    ))
                ->add( 'Domain', Type\ChoiceType::class, array(
                    'choices' => array_flip($domains),
                    'expanded' => false,
                    'multiple' => false,
                    'constraints' => new Assert\Choice(array_keys($domains)),
                    'label' => "À quelle branche appartient votre entreprise ?",
                    'placeholder' => '-sélectionner-',
                    'required' => true,
                    ))
                ->add( 'Domain_other', Type\TextType::class, array(
                    'label' => "Si autre, précisez",
                    'required' => false ,
                    ))
                ->add( 'Nombre_salaries_etablissement', Type\IntegerType::class, array(
                    'label' => "Nombre de salariés dans votre  établissement (facultatif) :",
                    'required' => false ,
                    ))
                ->add( 'Nombre_salaries_entreprise', Type\IntegerType::class, array(
                    'label' => "Nombre total de salariés dans votre entreprise (facultatif) :",
                    'required' => false ,
                    ))
            ;
        }
        $form = $formBuilder->getForm();

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $formData = $form->getData();
            // var_dump($formData);die;
            $data = array_merge($sessionData, $formData);
            $this->get('session')->set('data',$data);

            if ($idPage > count($likertQuestions)) {

                $time = time();
                $data = array_merge(array('dateFin' => $time),$data);

                $dataList = "";
                foreach ($data as $key => &$value) {
                    if (is_array($value)) {
                        $value = implode('-', $value);
                    }
                    $dataList .= $key .': '. $value. "\n";
                }

//                $message = \Swift_Message::newInstance()
//                    ->setSubject('[EnquêteVieAuTravail] Nouvelle réponse')
//                    ->setFrom(array('noreply@univ-nantes.fr'))
//                    ->setTo(array('vincent.beauvivre@gmail.com', 'kristina.beauvivre@gmail.com'))
//                    ->setBody("Une nouvelle réponse au formulaire :\n\n".$dataList);
//                $this->get('mailer')->send($message);

                $path = $this->container->getParameter('kernel.root_dir'). '/../app/Responses/';
                $handle = fopen($path. '_toutes.csv', 'a');
                fputcsv($handle, $data, ';');
                fclose($handle);

                $handle = fopen($path.$time.'.csv', 'w');
                fwrite($handle, $dataList);
                fclose($handle);

                $this->get('session')->set('data',null);

                $nextRoute = 'thanks';
            } else {
                $nextRoute = 'survey';
            }

            return $this->redirectToRoute($nextRoute);
        }

        // display the form
        return $this->render('form.html.twig', [
            'form' => $form->createView(),
            'shownPage' => $idPage,
            'scales' => array_keys($likertScales),
            'pages' => range(1, count($likertQuestions) + 1),
            'isLastPage' => $isLastPage,
        ]);

    }

    private function getChoicesFromScale(array $likertQuestion)
    {
        if (!isset($likertQuestion['scale']) || !$likertQuestion['scale']) {
            return [];
        }

        $likertScales = $this->container->getParameter('likert_scales');
        $scale = $likertScales[$likertQuestion['scale']];
        switch ($likertQuestion['type']) {
            case 'likert':
            case 'osgood':
                return array_flip($scale);
            default:
                throw new \Exception('Cannot understand scale type');
        }


    }
}
