<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Testimonial
 *
 * @ORM\Table(name="testimonial")
 * @ORM\Entity
 */
class Testimonial
{
    /**
     * @var string
     *
     * @ORM\Column(name="type", type="string", length=50, nullable=false)
     */
    private $type;

    /**
     * @var string
     *
     * @ORM\Column(name="slider_id", type="text", length=255, nullable=false)
     */
    private $sliderId;

    /**
     * @var integer
     *
     * @ORM\Column(name="id_client", type="integer", nullable=false)
     */
    private $idClient;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=100, nullable=true)
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="location", type="string", length=110, nullable=false)
     */
    private $location;

    /**
     * @var string
     *
     * @ORM\Column(name="projects", type="string", length=100, nullable=false)
     */
    private $projects;

    /**
     * @var string
     *
     * @ORM\Column(name="quote", type="string", length=255, nullable=false)
     */
    private $quote;

    /**
     * @var string
     *
     * @ORM\Column(name="info", type="string", length=255, nullable=false)
     */
    private $info;

    /**
     * @var string
     *
     * @ORM\Column(name="testimonial_page_title", type="string", length=120, nullable=false)
     */
    private $testimonialPageTitle;

    /**
     * @var string
     *
     * @ORM\Column(name="long_testimonial", type="text", length=65535, nullable=false)
     */
    private $longTestimonial;

    /**
     * @var string
     *
     * @ORM\Column(name="video", type="string", length=255, nullable=true)
     */
    private $video;

    /**
     * @var string
     *
     * @ORM\Column(name="video_caption", type="string", length=255, nullable=false)
     */
    private $videoCaption;

    /**
     * @var string
     *
     * @ORM\Column(name="feature_image", type="string", length=255, nullable=true)
     */
    private $featureImage;

    /**
     * @var string
     *
     * @ORM\Column(name="battenberg_image", type="string", length=255, nullable=true)
     */
    private $battenbergImage;

    /**
     * @var boolean
     *
     * @ORM\Column(name="status", type="boolean", nullable=false)
     */
    private $status;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="added", type="datetime", nullable=false)
     */
    private $added;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="updated", type="datetime", nullable=false)
     */
    private $updated;

    /**
     * @var integer
     *
     * @ORM\Column(name="id_testimonial", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idTestimonial;



    /**
     * Set type
     *
     * @param string $type
     *
     * @return Testimonial
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set sliderId
     *
     * @param string $sliderId
     *
     * @return Testimonial
     */
    public function setSliderId($sliderId)
    {
        $this->sliderId = $sliderId;

        return $this;
    }

    /**
     * Get sliderId
     *
     * @return string
     */
    public function getSliderId()
    {
        return $this->sliderId;
    }

    /**
     * Set idClient
     *
     * @param integer $idClient
     *
     * @return Testimonial
     */
    public function setIdClient($idClient)
    {
        $this->idClient = $idClient;

        return $this;
    }

    /**
     * Get idClient
     *
     * @return integer
     */
    public function getIdClient()
    {
        return $this->idClient;
    }

    /**
     * Set name
     *
     * @param string $name
     *
     * @return Testimonial
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set location
     *
     * @param string $location
     *
     * @return Testimonial
     */
    public function setLocation($location)
    {
        $this->location = $location;

        return $this;
    }

    /**
     * Get location
     *
     * @return string
     */
    public function getLocation()
    {
        return $this->location;
    }

    /**
     * Set projects
     *
     * @param string $projects
     *
     * @return Testimonial
     */
    public function setProjects($projects)
    {
        $this->projects = $projects;

        return $this;
    }

    /**
     * Get projects
     *
     * @return string
     */
    public function getProjects()
    {
        return $this->projects;
    }

    /**
     * Set quote
     *
     * @param string $quote
     *
     * @return Testimonial
     */
    public function setQuote($quote)
    {
        $this->quote = $quote;

        return $this;
    }

    /**
     * Get quote
     *
     * @return string
     */
    public function getQuote()
    {
        return $this->quote;
    }

    /**
     * Set info
     *
     * @param string $info
     *
     * @return Testimonial
     */
    public function setInfo($info)
    {
        $this->info = $info;

        return $this;
    }

    /**
     * Get info
     *
     * @return string
     */
    public function getInfo()
    {
        return $this->info;
    }

    /**
     * Set testimonialPageTitle
     *
     * @param string $testimonialPageTitle
     *
     * @return Testimonial
     */
    public function setTestimonialPageTitle($testimonialPageTitle)
    {
        $this->testimonialPageTitle = $testimonialPageTitle;

        return $this;
    }

    /**
     * Get testimonialPageTitle
     *
     * @return string
     */
    public function getTestimonialPageTitle()
    {
        return $this->testimonialPageTitle;
    }

    /**
     * Set longTestimonial
     *
     * @param string $longTestimonial
     *
     * @return Testimonial
     */
    public function setLongTestimonial($longTestimonial)
    {
        $this->longTestimonial = $longTestimonial;

        return $this;
    }

    /**
     * Get longTestimonial
     *
     * @return string
     */
    public function getLongTestimonial()
    {
        return $this->longTestimonial;
    }

    /**
     * Set video
     *
     * @param string $video
     *
     * @return Testimonial
     */
    public function setVideo($video)
    {
        $this->video = $video;

        return $this;
    }

    /**
     * Get video
     *
     * @return string
     */
    public function getVideo()
    {
        return $this->video;
    }

    /**
     * Set videoCaption
     *
     * @param string $videoCaption
     *
     * @return Testimonial
     */
    public function setVideoCaption($videoCaption)
    {
        $this->videoCaption = $videoCaption;

        return $this;
    }

    /**
     * Get videoCaption
     *
     * @return string
     */
    public function getVideoCaption()
    {
        return $this->videoCaption;
    }

    /**
     * Set featureImage
     *
     * @param string $featureImage
     *
     * @return Testimonial
     */
    public function setFeatureImage($featureImage)
    {
        $this->featureImage = $featureImage;

        return $this;
    }

    /**
     * Get featureImage
     *
     * @return string
     */
    public function getFeatureImage()
    {
        return $this->featureImage;
    }

    /**
     * Set battenbergImage
     *
     * @param string $battenbergImage
     *
     * @return Testimonial
     */
    public function setBattenbergImage($battenbergImage)
    {
        $this->battenbergImage = $battenbergImage;

        return $this;
    }

    /**
     * Get battenbergImage
     *
     * @return string
     */
    public function getBattenbergImage()
    {
        return $this->battenbergImage;
    }

    /**
     * Set status
     *
     * @param boolean $status
     *
     * @return Testimonial
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status
     *
     * @return boolean
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set added
     *
     * @param \DateTime $added
     *
     * @return Testimonial
     */
    public function setAdded($added)
    {
        $this->added = $added;

        return $this;
    }

    /**
     * Get added
     *
     * @return \DateTime
     */
    public function getAdded()
    {
        return $this->added;
    }

    /**
     * Set updated
     *
     * @param \DateTime $updated
     *
     * @return Testimonial
     */
    public function setUpdated($updated)
    {
        $this->updated = $updated;

        return $this;
    }

    /**
     * Get updated
     *
     * @return \DateTime
     */
    public function getUpdated()
    {
        return $this->updated;
    }

    /**
     * Get idTestimonial
     *
     * @return integer
     */
    public function getIdTestimonial()
    {
        return $this->idTestimonial;
    }
}
