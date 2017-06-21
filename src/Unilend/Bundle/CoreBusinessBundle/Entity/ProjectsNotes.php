<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ProjectsNotes
 *
 * @ORM\Table(name="projects_notes", uniqueConstraints={@ORM\UniqueConstraint(name="id_project", columns={"id_project"})})
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 */
class ProjectsNotes
{
    /**
     * @var \Unilend\Bundle\CoreBusinessBundle\Entity\Projects
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Bundle\CoreBusinessBundle\Entity\Projects")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_project", referencedColumnName="id_project")
     * })
     */
    private $idProject;

    /**
     * @var integer
     *
     * @ORM\Column(name="pre_scoring", type="integer")
     */
    private $preScoring;

    /**
     * @var float
     *
     * @ORM\Column(name="performance_fianciere", type="float", precision=10, scale=0)
     */
    private $performanceFianciere;

    /**
     * @var float
     *
     * @ORM\Column(name="structure", type="float", precision=10, scale=0)
     */
    private $structure;

    /**
     * @var float
     *
     * @ORM\Column(name="rentabilite", type="float", precision=10, scale=0)
     */
    private $rentabilite;

    /**
     * @var float
     *
     * @ORM\Column(name="tresorerie", type="float", precision=10, scale=0)
     */
    private $tresorerie;

    /**
     * @var float
     *
     * @ORM\Column(name="marche_opere", type="float", precision=10, scale=0)
     */
    private $marcheOpere;

    /**
     * @var float
     *
     * @ORM\Column(name="global", type="float", precision=10, scale=0)
     */
    private $global;

    /**
     * @var float
     *
     * @ORM\Column(name="individuel", type="float", precision=10, scale=0)
     */
    private $individuel;

    /**
     * @var float
     *
     * @ORM\Column(name="dirigeance", type="float", precision=10, scale=0)
     */
    private $dirigeance;

    /**
     * @var float
     *
     * @ORM\Column(name="indicateur_risque_dynamique", type="float", precision=10, scale=0)
     */
    private $indicateurRisqueDynamique;

    /**
     * @var string
     *
     * @ORM\Column(name="avis", type="text", length=16777215)
     */
    private $avis;

    /**
     * @var float
     *
     * @ORM\Column(name="note", type="float", precision=10, scale=0)
     */
    private $note;

    /**
     * @var float
     *
     * @ORM\Column(name="performance_fianciere_comite", type="float", precision=10, scale=0)
     */
    private $performanceFianciereComite;

    /**
     * @var float
     *
     * @ORM\Column(name="structure_comite", type="float", precision=10, scale=0)
     */
    private $structureComite;

    /**
     * @var float
     *
     * @ORM\Column(name="rentabilite_comite", type="float", precision=10, scale=0)
     */
    private $rentabiliteComite;

    /**
     * @var float
     *
     * @ORM\Column(name="tresorerie_comite", type="float", precision=10, scale=0)
     */
    private $tresorerieComite;

    /**
     * @var float
     *
     * @ORM\Column(name="marche_opere_comite", type="float", precision=10, scale=0)
     */
    private $marcheOpereComite;

    /**
     * @var float
     *
     * @ORM\Column(name="global_comite", type="float", precision=10, scale=0)
     */
    private $globalComite;

    /**
     * @var float
     *
     * @ORM\Column(name="individuel_comite", type="float", precision=10, scale=0)
     */
    private $individuelComite;

    /**
     * @var float
     *
     * @ORM\Column(name="dirigeance_comite", type="float", precision=10, scale=0)
     */
    private $dirigeanceComite;

    /**
     * @var float
     *
     * @ORM\Column(name="indicateur_risque_dynamique_comite", type="float", precision=10, scale=0)
     */
    private $indicateurRisqueDynamiqueComite;

    /**
     * @var float
     *
     * @ORM\Column(name="note_comite", type="float", precision=10, scale=0)
     */
    private $noteComite;

    /**
     * @var string
     *
     * @ORM\Column(name="avis_comite", type="text", length=16777215)
     */
    private $avisComite;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="added", type="datetime", nullable=false)
     */
    private $added;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="updated", type="datetime")
     */
    private $updated;

    /**
     * @var integer
     *
     * @ORM\Column(name="id_project_notes", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idProjectNotes;



    /**
     * Set idProject
     *
     * @param Projects $idProject
     *
     * @return ProjectsNotes
     */
    public function setIdProject(Projects $idProject)
    {
        $this->idProject = $idProject;

        return $this;
    }

    /**
     * Get idProject
     *
     * @return Projects
     */
    public function getIdProject()
    {
        return $this->idProject;
    }

    /**
     * Set preScoring
     *
     * @param integer $preScoring
     *
     * @return ProjectsNotes
     */
    public function setPreScoring($preScoring)
    {
        $this->preScoring = $preScoring;

        return $this;
    }

    /**
     * Get preScoring
     *
     * @return integer
     */
    public function getPreScoring()
    {
        return $this->preScoring;
    }

    /**
     * Set performanceFianciere
     *
     * @param float $performanceFianciere
     *
     * @return ProjectsNotes
     */
    public function setPerformanceFianciere($performanceFianciere)
    {
        $this->performanceFianciere = $performanceFianciere;

        return $this;
    }

    /**
     * Get performanceFianciere
     *
     * @return float
     */
    public function getPerformanceFianciere()
    {
        return $this->performanceFianciere;
    }

    /**
     * Set structure
     *
     * @param float $structure
     *
     * @return ProjectsNotes
     */
    public function setStructure($structure)
    {
        $this->structure = $structure;

        return $this;
    }

    /**
     * Get structure
     *
     * @return float
     */
    public function getStructure()
    {
        return $this->structure;
    }

    /**
     * Set rentabilite
     *
     * @param float $rentabilite
     *
     * @return ProjectsNotes
     */
    public function setRentabilite($rentabilite)
    {
        $this->rentabilite = $rentabilite;

        return $this;
    }

    /**
     * Get rentabilite
     *
     * @return float
     */
    public function getRentabilite()
    {
        return $this->rentabilite;
    }

    /**
     * Set tresorerie
     *
     * @param float $tresorerie
     *
     * @return ProjectsNotes
     */
    public function setTresorerie($tresorerie)
    {
        $this->tresorerie = $tresorerie;

        return $this;
    }

    /**
     * Get tresorerie
     *
     * @return float
     */
    public function getTresorerie()
    {
        return $this->tresorerie;
    }

    /**
     * Set marcheOpere
     *
     * @param float $marcheOpere
     *
     * @return ProjectsNotes
     */
    public function setMarcheOpere($marcheOpere)
    {
        $this->marcheOpere = $marcheOpere;

        return $this;
    }

    /**
     * Get marcheOpere
     *
     * @return float
     */
    public function getMarcheOpere()
    {
        return $this->marcheOpere;
    }

    /**
     * Set global
     *
     * @param float $global
     *
     * @return ProjectsNotes
     */
    public function setGlobal($global)
    {
        $this->global = $global;

        return $this;
    }

    /**
     * Get global
     *
     * @return float
     */
    public function getGlobal()
    {
        return $this->global;
    }

    /**
     * Set individuel
     *
     * @param float $individuel
     *
     * @return ProjectsNotes
     */
    public function setIndividuel($individuel)
    {
        $this->individuel = $individuel;

        return $this;
    }

    /**
     * Get individuel
     *
     * @return float
     */
    public function getIndividuel()
    {
        return $this->individuel;
    }

    /**
     * Set dirigeance
     *
     * @param float $dirigeance
     *
     * @return ProjectsNotes
     */
    public function setDirigeance($dirigeance)
    {
        $this->dirigeance = $dirigeance;

        return $this;
    }

    /**
     * Get dirigeance
     *
     * @return float
     */
    public function getDirigeance()
    {
        return $this->dirigeance;
    }

    /**
     * Set indicateurRisqueDynamique
     *
     * @param float $indicateurRisqueDynamique
     *
     * @return ProjectsNotes
     */
    public function setIndicateurRisqueDynamique($indicateurRisqueDynamique)
    {
        $this->indicateurRisqueDynamique = $indicateurRisqueDynamique;

        return $this;
    }

    /**
     * Get indicateurRisqueDynamique
     *
     * @return float
     */
    public function getIndicateurRisqueDynamique()
    {
        return $this->indicateurRisqueDynamique;
    }

    /**
     * Set avis
     *
     * @param string $avis
     *
     * @return ProjectsNotes
     */
    public function setAvis($avis)
    {
        $this->avis = $avis;

        return $this;
    }

    /**
     * Get avis
     *
     * @return string
     */
    public function getAvis()
    {
        return $this->avis;
    }

    /**
     * Set note
     *
     * @param float $note
     *
     * @return ProjectsNotes
     */
    public function setNote($note)
    {
        $this->note = $note;

        return $this;
    }

    /**
     * Get note
     *
     * @return float
     */
    public function getNote()
    {
        return $this->note;
    }

    /**
     * Set performanceFianciereComite
     *
     * @param float $performanceFianciereComite
     *
     * @return ProjectsNotes
     */
    public function setPerformanceFianciereComite($performanceFianciereComite)
    {
        $this->performanceFianciereComite = $performanceFianciereComite;

        return $this;
    }

    /**
     * Get performanceFianciereComite
     *
     * @return float
     */
    public function getPerformanceFianciereComite()
    {
        return $this->performanceFianciereComite;
    }

    /**
     * Set structureComite
     *
     * @param float $structureComite
     *
     * @return ProjectsNotes
     */
    public function setStructureComite($structureComite)
    {
        $this->structureComite = $structureComite;

        return $this;
    }

    /**
     * Get structureComite
     *
     * @return float
     */
    public function getStructureComite()
    {
        return $this->structureComite;
    }

    /**
     * Set rentabiliteComite
     *
     * @param float $rentabiliteComite
     *
     * @return ProjectsNotes
     */
    public function setRentabiliteComite($rentabiliteComite)
    {
        $this->rentabiliteComite = $rentabiliteComite;

        return $this;
    }

    /**
     * Get rentabiliteComite
     *
     * @return float
     */
    public function getRentabiliteComite()
    {
        return $this->rentabiliteComite;
    }

    /**
     * Set tresorerieComite
     *
     * @param float $tresorerieComite
     *
     * @return ProjectsNotes
     */
    public function setTresorerieComite($tresorerieComite)
    {
        $this->tresorerieComite = $tresorerieComite;

        return $this;
    }

    /**
     * Get tresorerieComite
     *
     * @return float
     */
    public function getTresorerieComite()
    {
        return $this->tresorerieComite;
    }

    /**
     * Set marcheOpereComite
     *
     * @param float $marcheOpereComite
     *
     * @return ProjectsNotes
     */
    public function setMarcheOpereComite($marcheOpereComite)
    {
        $this->marcheOpereComite = $marcheOpereComite;

        return $this;
    }

    /**
     * Get marcheOpereComite
     *
     * @return float
     */
    public function getMarcheOpereComite()
    {
        return $this->marcheOpereComite;
    }

    /**
     * Set globalComite
     *
     * @param float $globalComite
     *
     * @return ProjectsNotes
     */
    public function setGlobalComite($globalComite)
    {
        $this->globalComite = $globalComite;

        return $this;
    }

    /**
     * Get globalComite
     *
     * @return float
     */
    public function getGlobalComite()
    {
        return $this->globalComite;
    }

    /**
     * Set individuelComite
     *
     * @param float $individuelComite
     *
     * @return ProjectsNotes
     */
    public function setIndividuelComite($individuelComite)
    {
        $this->individuelComite = $individuelComite;

        return $this;
    }

    /**
     * Get individuelComite
     *
     * @return float
     */
    public function getIndividuelComite()
    {
        return $this->individuelComite;
    }

    /**
     * Set dirigeanceComite
     *
     * @param float $dirigeanceComite
     *
     * @return ProjectsNotes
     */
    public function setDirigeanceComite($dirigeanceComite)
    {
        $this->dirigeanceComite = $dirigeanceComite;

        return $this;
    }

    /**
     * Get dirigeanceComite
     *
     * @return float
     */
    public function getDirigeanceComite()
    {
        return $this->dirigeanceComite;
    }

    /**
     * Set indicateurRisqueDynamiqueComite
     *
     * @param float $indicateurRisqueDynamiqueComite
     *
     * @return ProjectsNotes
     */
    public function setIndicateurRisqueDynamiqueComite($indicateurRisqueDynamiqueComite)
    {
        $this->indicateurRisqueDynamiqueComite = $indicateurRisqueDynamiqueComite;

        return $this;
    }

    /**
     * Get indicateurRisqueDynamiqueComite
     *
     * @return float
     */
    public function getIndicateurRisqueDynamiqueComite()
    {
        return $this->indicateurRisqueDynamiqueComite;
    }

    /**
     * Set noteComite
     *
     * @param float $noteComite
     *
     * @return ProjectsNotes
     */
    public function setNoteComite($noteComite)
    {
        $this->noteComite = $noteComite;

        return $this;
    }

    /**
     * Get noteComite
     *
     * @return float
     */
    public function getNoteComite()
    {
        return $this->noteComite;
    }

    /**
     * Set avisComite
     *
     * @param string $avisComite
     *
     * @return ProjectsNotes
     */
    public function setAvisComite($avisComite)
    {
        $this->avisComite = $avisComite;

        return $this;
    }

    /**
     * Get avisComite
     *
     * @return string
     */
    public function getAvisComite()
    {
        return $this->avisComite;
    }

    /**
     * Set added
     *
     * @param \DateTime $added
     *
     * @return ProjectsNotes
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
     * @return ProjectsNotes
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
     * Get idProjectNotes
     *
     * @return integer
     */
    public function getIdProjectNotes()
    {
        return $this->idProjectNotes;
    }

    /**
     * @ORM\PrePersist
     */
    public function setAddedValue()
    {
        if (! $this->added instanceof \DateTime || 1 > $this->getAdded()->getTimestamp()) {
            $this->added = new \DateTime();
        }
    }

    /**
     * @ORM\PreUpdate
     */
    public function setUpdatedValue()
    {
        $this->updated = new \DateTime();
    }
}
