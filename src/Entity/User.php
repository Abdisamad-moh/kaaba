<?php

namespace App\Entity;

use Carbon\Carbon;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\UserRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\HasLifecycleCallbacks;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
#[HasLifecycleCallbacks]

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
#[UniqueEntity(fields: ['email'], message: 'There is already an account with this email')]
#[UniqueEntity(fields: ['username'], message: 'There is already an account with this username')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    use Timestamps;
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['job_list'])]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    private ?string $email = null;

    /**
     * @var list<string> The user roles
     */
    #[ORM\Column]
    private array $roles = [];

    /**
     * @var string The hashed password
     */
    #[ORM\Column(nullable: true)]
    private ?string $password = null;

    #[ORM\Column(length: 255)]
    #[Groups(['job_list'])]
    private ?string $name = null;

    #[ORM\Column]
    private bool $isVerified = false;

    #[ORM\Column(length: 255)]
    private ?string $username = null;

    #[ORM\Column(nullable: false)]
    private ?bool $status = null;

    #[ORM\Column(length: 255, nullable: false)]
    private ?string $type = null;

  

 
    private Collection $jobSeekerExperiences;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $profile = null;



    #[ORM\Column(length: 255, nullable: true)]
    private ?string $googleId = null;

    #[ORM\Column(nullable: true)]
    private ?int $otp = null;


     #[ORM\Column(length: 255, nullable: true)]
    private ?string $reset_token = null;

    




  


    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $last_active = null;

    

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $otpExpiration = null;

    #[ORM\Column(options: ['default' => true])]
    private ?bool $otpEnabled = true;

    #[ORM\Column(options: ['default' => 0])]
    private ?int $otpAttempts = 0;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $resetTokenExpiration = null;

    

    #[ORM\Column(nullable: true)]
    private ?bool $is_deleted = null;

  

    /**
     * @var Collection<int, KaabaInstitute>
     */
    #[ORM\OneToMany(targetEntity: KaabaInstitute::class, mappedBy: 'manager')]
    private Collection $kaabaInstitutes;

    /**
     * @var Collection<int, KaabaSmsLog>
     */
    #[ORM\OneToMany(targetEntity: KaabaSmsLog::class, mappedBy: 'created_by')]
    private Collection $kaabaSmsLogs;


    public function __construct()
    {
        $this->kaabaInstitutes = new ArrayCollection();
        $this->kaabaSmsLogs = new ArrayCollection();
    }

    

   

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     *
     * @return list<string>
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    /**
     * @param list<string> $roles
     */
    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function isVerified(): bool
    {
        return $this->isVerified;
    }

    public function setVerified(bool $isVerified): static
    {
        $this->isVerified = $isVerified;

        return $this;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(?string $username): static
    {
        $this->username = $username;

        return $this;
    }

    public function isStatus(): ?bool
    {
        return $this->status;
    }

    public function setStatus(?bool $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(?string $type): static
    {
        $this->type = $type;

        return $this;
    }

   

    public function getProfile(): ?string
    {
        return $this->profile;
    }

    public function setProfile(?string $profile): static
    {
        $this->profile = $profile;

        return $this;
    }

    



    public function getLastLogin(): string
{
    if (!$this->getLastActive()) {
        return 'Inactive';
    }

    return Carbon::instance($this->last_active)->diffForHumans();
}

  

    public function getGoogleId(): ?string
    {
        return $this->googleId;
    }

    public function setGoogleId(?string $googleId): static
    {
        $this->googleId = $googleId;

        return $this;
    }

    public function getOtp(): ?int
    {
        return $this->otp;
    }

    public function setOtp(?int $otp): static
    {
        $this->otp = $otp;

        return $this;
    }

    

    

    

    public function getResetToken(): ?string
    {
        return $this->reset_token;
    }

    public function setResetToken(?string $reset_token): static
    {
        $this->reset_token = $reset_token;

        return $this;
    }

    public function getLastActive(): ?\DateTimeInterface
    {
        return $this->last_active;
    }

    public function setLastActive(?\DateTimeInterface $last_active): static
    {
        $this->last_active = $last_active;

        return $this;
    }

    

    public function getOtpExpiration(): ?\DateTimeInterface
    {
        return $this->otpExpiration;
    }

    public function setOtpExpiration(?\DateTimeInterface $otpExpiration): static
    {
        $this->otpExpiration = $otpExpiration;

        return $this;
    }

    public function isOtpEnabled(): ?bool
    {
        return $this->otpEnabled;
    }

    public function setOtpEnabled(bool $otpEnabled): static
    {
        $this->otpEnabled = $otpEnabled;

        return $this;
    }

    public function getOtpAttempts(): ?int
    {
        return $this->otpAttempts;
    }

    public function setOtpAttempts(int $otpAttempts): static
    {
        $this->otpAttempts = $otpAttempts;

        return $this;
    }

    public function getResetTokenExpiration(): ?\DateTimeInterface
    {
        return $this->resetTokenExpiration;
    }

    public function setResetTokenExpiration(?\DateTimeInterface $resetTokenExpiration): static
    {
        $this->resetTokenExpiration = $resetTokenExpiration;

        return $this;
    }

    

    public function isDeleted(): ?bool
    {
        return $this->is_deleted;
    }

    public function setDeleted(?bool $is_deleted): static
    {
        $this->is_deleted = $is_deleted;

        return $this;
    }

    

    /**
     * @return Collection<int, KaabaInstitute>
     */
    public function getKaabaInstitutes(): Collection
    {
        return $this->kaabaInstitutes;
    }

    public function addKaabaInstitute(KaabaInstitute $kaabaInstitute): static
    {
        if (!$this->kaabaInstitutes->contains($kaabaInstitute)) {
            $this->kaabaInstitutes->add($kaabaInstitute);
            $kaabaInstitute->setManager($this);
        }

        return $this;
    }

    public function removeKaabaInstitute(KaabaInstitute $kaabaInstitute): static
    {
        if ($this->kaabaInstitutes->removeElement($kaabaInstitute)) {
            // set the owning side to null (unless already changed)
            if ($kaabaInstitute->getManager() === $this) {
                $kaabaInstitute->setManager(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, KaabaSmsLog>
     */
    public function getKaabaSmsLogs(): Collection
    {
        return $this->kaabaSmsLogs;
    }

    public function addKaabaSmsLog(KaabaSmsLog $kaabaSmsLog): static
    {
        if (!$this->kaabaSmsLogs->contains($kaabaSmsLog)) {
            $this->kaabaSmsLogs->add($kaabaSmsLog);
            $kaabaSmsLog->setCreatedBy($this);
        }

        return $this;
    }

    public function removeKaabaSmsLog(KaabaSmsLog $kaabaSmsLog): static
    {
        if ($this->kaabaSmsLogs->removeElement($kaabaSmsLog)) {
            // set the owning side to null (unless already changed)
            if ($kaabaSmsLog->getCreatedBy() === $this) {
                $kaabaSmsLog->setCreatedBy(null);
            }
        }

        return $this;
    }
}