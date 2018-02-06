<?php

namespace AppBundle\Entity;

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
     * @ORM\Column(name="email_dates", type="array")
     */
    private $emailDates;

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

    public function __construct()
    {
        $this->setCreatedDate(new \DateTime());
        $this->isFinished = false;
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
        $this->key = substr(base_convert(md5($email), 16, 36), 0, 8);
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

    public function getEmailDates()
    {
        return array_map(
            function ($timestamp) {
                $date = new \DateTime();
                return $date->setTimestamp($timestamp);
            },
            $this->emailDates
        );
    }

    public function addEmailDate(\DateTime $date)
    {
        $this->emailDates[] = $date->getTimestamp();
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
}
