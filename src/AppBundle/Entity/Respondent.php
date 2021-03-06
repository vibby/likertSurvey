<?php

namespace AppBundle\Entity;

use AppBundle\Tools\Fingerprinter;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table(name="likertsurvey_respondent")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\RespondentRepository")
 * @UniqueEntity(fields={"email"}, message="This email is already used.")
 * @UniqueEntity(fields={"key"}, message="La clé générée est déjà utilisée")
 */
class Respondent
{
    const SOURCE_ADMIN = 'admin';
    const SOURCE_HOME = 'home';
    const SOURCE_AFTER = 'after';
    const SOURCE_AFTER_MANAGER = 'addManager';
    const SOURCE_AFTER_SUBORD = 'addSubord';
    const SOURCE_AFTER_COLLEAGUE = 'addColleag';

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
     * @ORM\Column(type="string", length=200, unique=true, nullable=false)
     * @Assert\Email()
     * @Assert\NotBlank()
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
     * @ORM\Column(name="last_connection_date", type="datetime", nullable=true)
     */
    private $lastConnectionDate;

    /**
     * @ORM\Column(name="feedback_team", type="boolean", nullable=true)
     */
    private $feedbackTeam;

    /**
     * @ORM\Column(name="revived_count", type="integer")
     */
    private $revivedCount = 0;

    /**
     * @ORM\Column(name="is_manager", type="integer", nullable=true)
     */
    private $isManager;

    /**
     * @ORM\Column(name="source", type="string", length=10, nullable=false)
     */
    private $source;

    /**
     * @ORM\Column(name="domain", type="string", length=80, nullable=false)
     */
    private $domain;

    /**
     * @ORM\OneToMany(targetEntity="Respondent", mappedBy="manager",cascade={"persist"})
     */
    private $subordinates;

    /**
     * @ORM\ManyToOne(targetEntity="Respondent", inversedBy="subordinates", cascade={"persist"})
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
        $this->key = Fingerprinter::fingerprint('k3y$'.$email);
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

    public function getRevivedCount()
    {
        return $this->revivedCount;
    }

    public function setRevivedCount($revivedCount)
    {
        $this->revivedCount = $revivedCount;
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

    public function getLastConnectionDate()
    {
        return $this->lastConnectionDate;
    }

    public function setLastConnectionDate($lastConnectionDate)
    {
        $this->lastConnectionDate = $lastConnectionDate;
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

    public function setSource($source)
    {
        $this->source = $source;
    }

    public function getSource()
    {
        return $this->source;
    }

    public function setDomain($domain)
    {
        $this->domain = $domain;
    }

    public function getDomain()
    {
        return $this->domain;
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
        if ($respondent->getManager()) {
            return;
        }
        if (!$this->hasSubordinate($respondent)) {
            $this->subordinates->add($respondent);
            $respondent->setManager($this);
        }
    }

    public function removeSubordinate(Respondent $respondent)
    {
        if ($this->subordinates->contains($respondent)) {
            $respondent->unsetManager();
            $this->subordinates->removeElement($respondent);
        }
    }

    public function hasSubordinate($respondent)
    {
        $emails = [];
        foreach ($this->subordinates as $subordinate) {
            $emails[] = $subordinate->getEmail();
        }

        return in_array($respondent->getEmail(), $emails);
    }

    public function getSubordinates()
    {
        return $this->subordinates;
    }

    public function unsetManager()
    {
        $this->manager = null;
    }

    public function setManager(Respondent $manager)
    {
        if (!$this->manager) {
            $this->manager = $manager;
            if (!$manager->hasSubordinate($this)) {
                $manager->addSubordinate($this);
            }
        }
    }

    public function getManager()
    {
        return $this->manager;
    }

    public function getIsManager()
    {
        return $this->isManager;
    }

    public function setIsManager($isManager)
    {
        $this->isManager = $isManager;
    }

    public function setKey($key)
    {
        $this->key = $key;
    }
}
