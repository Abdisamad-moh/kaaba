<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Symfony\Component\Uid\Uuid;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;
use App\Repository\KaabaApplicationRepository;
use Doctrine\Common\Collections\ArrayCollection;

#[ORM\Entity(repositoryClass: KaabaApplicationRepository::class)]
class KaabaApplication
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $full_name = null;

    #[ORM\ManyToOne(inversedBy: 'kaabaApplications')]
    #[ORM\JoinColumn(nullable: false)]
    private ?KaabaRegion $region = null;

    #[ORM\ManyToOne(inversedBy: 'kaabaApplications')]
    #[ORM\JoinColumn(nullable: false)]
    private ?KaabaGender $gender = null;

    #[ORM\ManyToOne(inversedBy: 'kaabaApplications')]
    private ?KaabaDistrict $district = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $date_of_birth = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $town = null;

    #[ORM\ManyToOne(inversedBy: 'kaabaApplications')]
    private ?KaabaNationality $nationality = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $village = null;

    #[ORM\Column(length: 255)]
    private ?string $phone = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $email = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $disability = null;

    #[ORM\ManyToOne(targetEntity: KaabaIdentityType::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?KaabaIdentityType $identity_type = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $identity_attachment = null;

    #[ORM\ManyToOne(inversedBy: 'kaabaApplications')]
    #[ORM\JoinColumn(nullable: true)]
    private ?KaabaInstitute $institute = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $secondary_school = null;

    #[ORM\ManyToOne(inversedBy: 'kaabaApplicationsSchools')]
    #[ORM\JoinColumn(nullable: true)]
    private ?KaabaRegion $secondary_region = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $secondary_graduation_year = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $secondary_grade = null;

    #[ORM\ManyToOne(inversedBy: 'kaabaApplications')]
    private ?KaabaQualification $highest_qualification = null;

    #[ORM\ManyToOne(inversedBy: 'kaabaApplications')]
    private ?KaabaCourse $course = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $highest_qualification_detail = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $institution_name = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $location = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $start_year = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $end_year = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $qualification = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $minimum_grade = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $certificate_attachment = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $willingness_declaration_attachment = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $needs_statement_attachment = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $other_documents_attachment = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $created_at = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $application_date = null;


    #[ORM\ManyToOne(inversedBy: 'kaabaApplications')]
    #[ORM\JoinColumn(nullable: true)]
    private ?KaabaApplicationStatus $status = null;

    #[ORM\ManyToOne(inversedBy: 'kaabaApplications')]
    #[ORM\JoinColumn(nullable: false)]
    private ?KaabaScholarship $scholarship = null;

    #[ORM\Column(type: Types::GUID)]
    private ?string $uuid = null;

#[ORM\Column(length: 255, nullable: true)]
private ?string $disability_type = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $disability_explanation = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $literacy_level = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $numeracy_level = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $recent_education = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $literacy_numeracy_qualification = null;


 #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $applied_date = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $shortlisted_date = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $accepted_date = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $rejected_date = null;


#[ORM\OneToMany(mappedBy: 'application', targetEntity: KaabaApplicationLog::class, cascade: ['persist'])]
private Collection $logs;



#[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
private ?\DateTimeInterface $waitlisted_date = null;

    public function __construct()
    {
        $this->created_at = new \DateTime();        
        $this->application_date = new \DateTime();  
        $this->uuid = Uuid::v4();
        $this->applied_date = new \DateTime(); 
    $this->logs = new ArrayCollection();


    }

    public function getId(): ?int
    {
        return $this->id;
    }
    public function getUuid(): ?string
    {
        return $this->uuid;
    }

    public function setUuid(string $uuid): static
    {
        $this->uuid = $uuid;

        return $this;
    }

    public function getFullName(): ?string
    {
        return $this->full_name;
    }

    public function setFullName(string $full_name): static
    {
        $this->full_name = $full_name;

        return $this;
    }

    public function getRegion(): ?KaabaRegion
    {
        return $this->region;
    }

    public function setRegion(?KaabaRegion $region): static
    {
        $this->region = $region;

        return $this;
    }

    public function getGender(): ?KaabaGender
    {
        return $this->gender;
    }

    public function setGender(?KaabaGender $gender): static
    {
        $this->gender = $gender;

        return $this;
    }

    public function getDistrict(): ?KaabaDistrict
    {
        return $this->district;
    }

    public function setDistrict(?KaabaDistrict $district): static
    {
        $this->district = $district;

        return $this;
    }

    public function getDateOfBirth(): ?\DateTimeInterface
    {
        return $this->date_of_birth;
    }

    public function setDateOfBirth(?\DateTimeInterface $date_of_birth): static
    {
        $this->date_of_birth = $date_of_birth;

        return $this;
    }

    public function getTown(): ?string
    {
        return $this->town;
    }

    public function setTown(?string $town): static
    {
        $this->town = $town;

        return $this;
    }

    public function getNationality(): ?KaabaNationality
    {
        return $this->nationality;
    }

    public function setNationality(?KaabaNationality $nationality): static
    {
        $this->nationality = $nationality;

        return $this;
    }

    public function getVillage(): ?string
    {
        return $this->village;
    }

    public function setVillage(?string $village): static
    {
        $this->village = $village;

        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(string $phone): static
    {
        $this->phone = $phone;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getDisability(): ?string
    {
        return $this->disability;
    }

    public function setDisability(?string $disability): static
    {
        $this->disability = $disability;

        return $this;
    }

    public function getIdentityType(): ?KaabaIdentityType
    {
        return $this->identity_type;
    }

    public function setIdentityType(?KaabaIdentityType $identity_type): static
    {
        $this->identity_type = $identity_type;

        return $this;
    }

    public function getIdentityAttachment(): ?string
    {
        return $this->identity_attachment;
    }

    public function setIdentityAttachment(?string $identity_attachment): static
    {
        $this->identity_attachment = $identity_attachment;

        return $this;
    }

    public function getInstitute(): ?KaabaInstitute
    {
        return $this->institute;
    }

    public function setInstitute(?KaabaInstitute $institute): static
    {
        $this->institute = $institute;

        return $this;
    }

    public function getSecondarySchool(): ?string
    {
        return $this->secondary_school;
    }

    public function setSecondarySchool(?string $secondary_school): static
    {
        $this->secondary_school = $secondary_school;

        return $this;
    }

    public function getSecondaryRegion(): ?KaabaRegion
    {
        return $this->secondary_region;
    }

    public function setSecondaryRegion(?KaabaRegion $secondary_region): static
    {
        $this->secondary_region = $secondary_region;

        return $this;
    }

    public function getSecondaryGraduationYear(): ?string
    {
        return $this->secondary_graduation_year;
    }

    public function setSecondaryGraduationYear(?string $secondary_graduation_year): static
    {
        $this->secondary_graduation_year = $secondary_graduation_year;

        return $this;
    }

    public function getSecondaryGrade(): ?string
    {
        return $this->secondary_grade;
    }

    public function setSecondaryGrade(?string $secondary_grade): static
    {
        $this->secondary_grade = $secondary_grade;

        return $this;
    }

    public function getHighestQualification(): ?KaabaQualification
    {
        return $this->highest_qualification;
    }

    public function setHighestQualification(?KaabaQualification $highest_qualification): static
    {
        $this->highest_qualification = $highest_qualification;

        return $this;
    }
    public function getCourse(): ?KaabaCourse
    {
        return $this->course;
    }

    public function setCourse(?KaabaCourse $course): static
    {
        $this->course = $course;

        return $this;
    }

    public function getHighestQualificationDetail(): ?string
    {
        return $this->highest_qualification_detail;
    }

    public function setHighestQualificationDetail(?string $highest_qualification_detail): static
    {
        $this->highest_qualification_detail = $highest_qualification_detail;

        return $this;
    }

    public function getInstitutionName(): ?string
    {
        return $this->institution_name;
    }

    public function setInstitutionName(?string $institution_name): static
    {
        $this->institution_name = $institution_name;

        return $this;
    }

    public function getLocation(): ?string
    {
        return $this->location;
    }

    public function setLocation(?string $location): static
    {
        $this->location = $location;

        return $this;
    }

    public function getStartYear(): ?string
    {
        return $this->start_year;
    }

    public function setStartYear(?string $start_year): static
    {
        $this->start_year = $start_year;

        return $this;
    }

    public function getEndYear(): ?string
    {
        return $this->end_year;
    }

    public function setEndYear(?string $end_year): static
    {
        $this->end_year = $end_year;

        return $this;
    }

    public function getQualification(): ?string
    {
        return $this->qualification;
    }

    public function setQualification(?string $qualification): static
    {
        $this->qualification = $qualification;

        return $this;
    }

    public function getMinimumGrade(): ?string
    {
        return $this->minimum_grade;
    }

    public function setMinimumGrade(?string $minimum_grade): static
    {
        $this->minimum_grade = $minimum_grade;

        return $this;
    }

    public function getCertificateAttachment(): ?string
    {
        return $this->certificate_attachment;
    }

    public function setCertificateAttachment(?string $certificate_attachment): static
    {
        $this->certificate_attachment = $certificate_attachment;

        return $this;
    }

    public function getWillingnessDeclarationAttachment(): ?string
    {
        return $this->willingness_declaration_attachment;
    }

    public function setWillingnessDeclarationAttachment(?string $willingness_declaration_attachment): static
    {
        $this->willingness_declaration_attachment = $willingness_declaration_attachment;

        return $this;
    }

    public function getNeedsStatementAttachment(): ?string
    {
        return $this->needs_statement_attachment;
    }

    public function setNeedsStatementAttachment(?string $needs_statement_attachment): static
    {
        $this->needs_statement_attachment = $needs_statement_attachment;

        return $this;
    }

    public function getOtherDocumentsAttachment(): ?string
    {
        return $this->other_documents_attachment;
    }

    public function setOtherDocumentsAttachment(?string $other_documents_attachment): static
    {
        $this->other_documents_attachment = $other_documents_attachment;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->created_at;
    }

    public function setCreatedAt(\DateTimeInterface $created_at): static
    {
        $this->created_at = $created_at;

        return $this;
    }

    public function getApplicationDate(): ?\DateTimeInterface
    {
        return $this->application_date;
    }

    public function setApplicationDate(\DateTimeInterface $application_date): static
    {
        $this->application_date = $application_date;

        return $this;
    }


    public function getStatus(): ?KaabaApplicationStatus
    {
        return $this->status;
    }

    public function setStatus(?KaabaApplicationStatus $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getScholarship(): ?KaabaScholarship
    {
        return $this->scholarship;
    }

    public function setScholarship(?KaabaScholarship $scholarship): static
    {
        $this->scholarship = $scholarship;

        return $this;
    }

    public function getDisabilityExplanation(): ?string
    {
        return $this->disability_explanation;
    }

    public function setDisabilityExplanation(?string $disability_explanation): static
    {
        $this->disability_explanation = $disability_explanation;

        return $this;
    }

    public function getLiteracyLevel(): ?string
    {
        return $this->literacy_level;
    }

    public function setLiteracyLevel(?string $literacy_level): static
    {
        $this->literacy_level = $literacy_level;

        return $this;
    }

    public function getNumeracyLevel(): ?string
    {
        return $this->numeracy_level;
    }

    public function setNumeracyLevel(?string $numeracy_level): static
    {
        $this->numeracy_level = $numeracy_level;

        return $this;
    }

    public function getRecentEducation(): ?string
    {
        return $this->recent_education;
    }

    public function setRecentEducation(?string $recent_education): static
    {
        $this->recent_education = $recent_education;

        return $this;
    }

    public function getLiteracyNumeracyQualification(): ?string
    {
        return $this->literacy_numeracy_qualification;
    }

    public function setLiteracyNumeracyQualification(?string $literacy_numeracy_qualification): static
    {
        $this->literacy_numeracy_qualification = $literacy_numeracy_qualification;

        return $this;
    }


   public function getAppliedDate(): ?\DateTimeInterface
    {
        return $this->applied_date;
    }

    public function setAppliedDate(?\DateTimeInterface $applied_date): static
    {
        $this->applied_date = $applied_date;

        return $this;
    }

    public function getShortlistedDate(): ?\DateTimeInterface
    {
        return $this->shortlisted_date;
    }

    public function setShortlistedDate(?\DateTimeInterface $shortlisted_date): static
    {
        $this->shortlisted_date = $shortlisted_date;

        return $this;
    }

    public function getAcceptedDate(): ?\DateTimeInterface
    {
        return $this->accepted_date;
    }

    public function setAcceptedDate(?\DateTimeInterface $accepted_date): static
    {
        $this->accepted_date = $accepted_date;

        return $this;
    }

    public function getRejectedDate(): ?\DateTimeInterface
    {
        return $this->rejected_date;
    }

    public function setRejectedDate(?\DateTimeInterface $rejected_date): static
    {
        $this->rejected_date = $rejected_date;

        return $this;
    }

// Add these methods:
public function getLogs(): Collection
{
    return $this->logs;
}

public function addLog(KaabaApplicationLog $log): static
{
    if (!$this->logs->contains($log)) {
        $this->logs->add($log);
        $log->setApplication($this);
    }

    return $this;
}

public function removeLog(KaabaApplicationLog $log): static
{
    if ($this->logs->removeElement($log)) {
        // set the owning side to null (unless already changed)
        if ($log->getApplication() === $this) {
            $log->setApplication(null);
        }
    }

    return $this;
}


public function getDisabilityType(): ?string
{
    return $this->disability_type;
}

public function setDisabilityType(?string $disability_type): static
{
    $this->disability_type = $disability_type;

    return $this;
}


public function getWaitlistedDate(): ?\DateTimeInterface
{
    return $this->waitlisted_date;
}

public function setWaitlistedDate(?\DateTimeInterface $waitlisted_date): static
{
    $this->waitlisted_date = $waitlisted_date;

    return $this;
}

// In KaabaApplication entity
public function wasRejectedBy(User $user): bool
{
    $logs = $this->getLogs();
    
    foreach ($logs as $log) {
        if ($log->getAction() === 'status_change' && 
            $log->getUser() === $user &&
            (strpos($log->getDescription(), "to 'Rejected'") !== false || 
             strpos($log->getDescription(), "to 'rejected'") !== false)) {
            return true;
        }
    }
    
    return false;
}
}
