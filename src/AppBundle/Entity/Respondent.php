<?php

namespace AppBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table(name="app_respondent")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\RespondentRepository")
 * @UniqueEntity(fields={"email"}, message="This email is already used.")
 * @UniqueEntity(fields={"key"}, message="The generated key is already used.")
 */
class Respondent
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\Column(name="access_key", type="string", length=64, unique=true)
     */
    private $key;

    /**
     * @ORM\Column(type="string", length=200, unique=true)
     * @Assert\Email()
     */
    private $email;

    /**
     * @ORM\Column(type="string", length=200, nullable=true)
     * @Assert\Email()
     */
    private $emailFeedback;

    /**
     * @ORM\Column(name="response", type="array")
     */
    private $response;

    /**
     * @ORM\Column(name="is_active", type="boolean")
     */
    private $isFinished;

    /**
     * @ORM\Column(name="export_dates", type="array")
     */
    private $exportDates;

    /**
     * @ORM\Column(name="date_finish", type="datetime", nullable=true)
     */
    private $finishDate;

    /**
     * @ORM\Column(name="date_start", type="datetime", nullable=true)
     */
    private $startDate;

    /**
     * @ORM\Column(name="date_created", type="datetime")
     */
    private $createdDate;

    /**
     * @ORM\Column(name="feedback_myself", type="boolean", nullable=true)
     */
    private $feedbackMyself;

    /**
     * @ORM\Column(name="feedback_team", type="boolean", nullable=true)
     */
    private $feedbackTeam;

    /**
     * @ORM\Column(name="revived", type="integer")
     */
    private $revived = 0;

    /**
     * @ORM\Column(name="is_manager", type="integer", nullable=true)
     */
    private $isManager;

    // ...
    /**
     * @ORM\OneToMany(targetEntity="Respondent", mappedBy="manager")
     */
    private $subordinates;

    /**
     * @ORM\ManyToOne(targetEntity="Respondent", inversedBy="subordinates")
     * @ORM\JoinColumn(name="manager_id", referencedColumnName="id")
     */
    private $manager;

    public function __construct()
    {
        $this->setCreatedDate(new \DateTime());
        $this->isFinished = false;
        $this->subordinates = new ArrayCollection();
    }

    public function __toString()
    {
        return $this->email;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getEmail()
    {
        return $this->email;
    }

    public function setEmail($email)
    {
        $this->email = $email;
        $salt = 'lræi\'—dfKC]ocIW}±ð©0…R!|R?è†UoRhLnm2,)Rlt/X®f!r~…×≠)iE3|';
        $this->key = substr(crypt($email, $salt), 0, 8);
    }

    public function getEmailFeedback()
    {
        return $this->emailFeedback;
    }

    public function setEmailFeedback($emailFeedback)
    {
        $this->emailFeedback = $emailFeedback;
    }

    public function getKey()
    {
        return $this->key;
    }

    public function getResponse()
    {
        return $this->response;
    }

    public function setResponse($response)
    {
        $this->response = $response;
    }

    public function getRevived()
    {
        return $this->revived;
    }

    public function setRevived($revived)
    {
        $this->revived = $revived;
    }

    public function getStartDate()
    {
        return $this->startDate;
    }

    public function setStartDate($startDate)
    {
        $this->startDate = $startDate;
    }

    public function getFinishDate()
    {
        return $this->finishDate;
    }

    public function setFinishDate($finishDate)
    {
        $this->finishDate = $finishDate;
    }

    public function getCreatedDate()
    {
        return $this->createdDate;
    }

    public function setCreatedDate($createdDate)
    {
        $this->createdDate = $createdDate;
    }

    public function getExportDates()
    {
        return array_map(
            function ($timestamp) {
                $date = new \DateTime();
                return $date->setTimestamp($timestamp);
            },
            $this->exportDates
        );
    }

    public function addEmailDate(\DateTime $date)
    {
        $this->exportDates[] = $date->getTimestamp();
    }

    public function setFinished($finished)
    {
        $this->isFinished = $finished;
    }

    public function isFinished()
    {
        return $this->isFinished;
    }

    public function setFeedbackMyself($feedbackMyself)
    {
        $this->feedbackMyself = $feedbackMyself;
    }

    public function isFeedbackMyself()
    {
        return $this->feedbackMyself;
    }

    public function setFeedbackTeam($feedbackTeam)
    {
        $this->feedbackTeam = $feedbackTeam;
    }

    public function isFeedbackTeam()
    {
        return $this->feedbackTeam;
    }

    public function addSubordinate(Respondent $respondent)
    {
        if (!$this->subordinates->contains($respondent)) {
            $respondent->setManager($this);
            $this->subordinates->add($respondent);
        }
    }

    public function removeSubordinate(Respondent $respondent)
    {
        if ($this->subordinates->contains($respondent)) {
            $respondent->setManager(null);
            $this->subordinates->remove($respondent);
        }
    }

    public function getSubordinates()
    {
        return $this->subordinates;
    }

    public function setManager(Respondent $manager)
    {
        if ($this->manager) {
            throw new \Exception('Cette personne est déjà définie.');
        }

        $manager->addSubordinate($this);
        $this->manager = $manager;
    }

    public function getManager()
    {
        return $this->manager;
    }

    public function getIsManager()
    {
        if (count($this->getSubordinates())) {
            return true;
        }
        return $this->isManager;
    }

    public function setIsManager($isManager)
    {
        $this->$isManager = $isManager;
    }
}
