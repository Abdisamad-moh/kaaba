<?php
// src/Service/TranslationService.php

namespace App\Service;

use Symfony\Component\HttpFoundation\RequestStack;

class TranslationService
{
    private RequestStack $requestStack;
    private array $translations;

    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
        $this->translations = $this->loadTranslations();
    }

    private function loadTranslations(): array
    {
        return [
            'en' => [
    'back_to_home' => 'Back to Home',

                // Application structure
                'application_form' => 'Application Form',
                'scholarship_information' => 'Scholarship Information',
                'description' => 'Description',
                'closing_date' => 'Closing Date',
                'personal_information' => 'Personal Information',
                'supporting_documents' => 'Supporting Documents',
                'next_step' => 'Next Step',
                'previous' => 'Previous',
                'submit_application' => 'Submit Application',
                'add_files' => 'Add Files',
                'drag_files_here' => 'Drag files here or click to browse',
                'select_option' => 'Select an option',
                'district_placeholder' => 'Select District',
                'optional' => 'optional',
                'select_institute_placeholder' => 'Select Institute first',
                'course_placeholder' => 'Select Course',


                // Personal Information Fields
                'full_name' => 'Full Name',
                'full_name_placeholder' => 'Enter your full name',
                'gender' => 'Gender',
                'gender_placeholder' => 'Select Gender',
                'phone' => 'Phone Number',
                'phone_placeholder' => 'Enter your phone number',
                'email' => 'Email Address',
                'email_placeholder' => 'Enter your email address',
                'nationality' => 'Nationality',
                'nationality_placeholder' => 'List of Countries',
                'date_of_birth' => 'Date of Birth',
                'date_placeholder' => 'mm/dd/yyyy',
                'region' => 'Region',
                'region_placeholder' => 'Select Region',
                'district' => 'District',
                'town' => 'Town',
                'town_placeholder' => 'Enter your town',
                'village' => 'Village',
                'village_placeholder' => 'Enter your village',
'select_region_first' => 'Select region first',

               // Literacy & Numeracy Section
'literacy_numeracy_section' => 'Literacy & Numeracy Level',
'literacy_level' => 'Literacy Level',
'literacy_level_placeholder' => 'Select literacy level',
'numeracy_level' => 'Numeracy Level',
'numeracy_level_placeholder' => 'Select numeracy level',
'literacy_numeracy_qualification' => 'Literacy & Numeracy Qualification',
'literacy_numeracy_qualification_placeholder' => 'Enter literacy and numeracy qualification',
'recent_education' => 'Most Recent Education',
'recent_education_placeholder' => 'Enter recent education details',

// Literacy Level Choices
'literacy_no_skills' => 'I do not possess reading or writing skills',
'literacy_limited' => 'I have limited proficiency in reading and writing',
'literacy_moderate' => 'I have moderate proficiency in reading and writing',
'literacy_proficient' => 'I am proficient in reading and writing',

// Numeracy Level Choices
'numeracy_no_skills' => 'I do not possess basic numeracy skills',
'numeracy_limited' => 'I have limited proficiency in numeracy',
'numeracy_moderate' => 'I have moderate proficiency in numeracy',
'numeracy_proficient' => 'I am proficient in numeracy',

                // Disability Section
                'disability_disclosure' => 'Disability Disclosure Section',
                'disability_question' => 'Do you have any disabilities or learning difficulties you would like us to be aware of?',
                'disability_explanation' => 'Please explain your disability or learning difficulty',
                'disability_explanation_placeholder' => 'Please describe your disability',

                // Identity Verification
                'personal_identification' => 'Personal Identification Verification',
                'identity_type' => 'Identity Type',
                'identity_type_placeholder' => 'Select identity type (optional)',
                'identity_attachment' => 'Identity Attachment',

                // Enrollment
                'enrollment_school' => 'Enrollment - School Selection',
                'enrollment_course' => 'Enrollment - Course Selection',
                'institute' => 'Institute',
                'institute_placeholder' => 'Select Institute',

                // Secondary Education
                'secondary_school_education' => 'Secondary School Education',
                'secondary_region' => 'Secondary Region',
                'secondary_school' => 'Secondary School',
                'secondary_school_placeholder' => 'Enter secondary school name',
                'secondary_graduation_year' => 'Secondary Graduation Year',
                'secondary_graduation_year_placeholder' => 'Enter graduation year',
                'secondary_grade' => 'Secondary Grade',
                'secondary_grade_placeholder' => 'Enter secondary grade',

                // Higher Education
                'higher_education' => 'Higher Education Qualification',
                'highest_qualification' => 'Highest Qualification',
                'highest_qualification_placeholder' => 'Select Your Highest Qualification',
                'highest_qualification_detail' => 'Highest Qualification Details',
                'highest_qualification_detail_placeholder' => 'Enter qualification details',

                // Last Education Institution
                'last_education' => 'Last Education Training Institution',
                'institution_name' => 'Institution Name',
                'institution_name_placeholder' => 'Enter institution name',
                'location' => 'Location',
                'location_placeholder' => 'Enter location',
                'start_year' => 'Start Year',
                'start_year_placeholder' => 'Enter start year',
                'end_year' => 'End Year',
                'end_year_placeholder' => 'Enter end year',
                'qualification' => 'Qualification',
                'qualification_placeholder' => 'Enter qualification',
                'minimum_grade' => 'Minimum Grade',
                'minimum_grade_placeholder' => 'Enter minimum grade/GPA',

                // Supporting Documents
                'certificates_attachment' => 'Certificates Attachment',
                'willingness_declaration' => 'Willingness Declaration Attachment',
                'needs_statement' => 'Needs Statement Attachment',
                'other_documents' => 'Other Documents Attachment',


//Declaration Section
                'declaration' => 'Declaration',
                'declaration_text' => 'I hereby affirm that all information provided in this application form is true, accurate, andcomplete to the best of my knowledge. I further express my earnest commitment to fullyengage with the learning opportunity offered through this scholarship. Due to financialconstraints, I have been unable to pursue studies at other institutions, and I amtherefore sincerely motivated to benefit from this program.',
              

// Acknowledgment Section
                'acknowledgement_agreement' => 'Acknowledgement and Agreement',
                'i_confirm_read_agree' => 'I confirm that I have read and agree to the terms and conditions stated above.',

                // Choices
                'yes' => 'Yes',
                'no' => 'No',
                'prefer_not_to_say' => 'Prefer not to say',
            ],
            'so' => [
    'back_to_home' => 'Dib ugu Noqo Bogga Hore',
'select_region_first' => 'Dooro gobolka marka hore',

   // Literacy Level Choices (Somali)
    'literacy_no_skills' => 'Ma haysano aqoon u ah akhriska ama qorista',
    'literacy_limited' => 'Waxaan haystaa aqoon kooban oo ku saabsan akhriska iyo qorista',
    'literacy_moderate' => 'Waxaan haystaa aqoon dhexdhexaad ah oo ku saabsan akhriska iyo qorista',
    'literacy_proficient' => 'Waxaan ku fiicanahay akhriska iyo qorista',

    // Numeracy Level Choices (Somali)
    'numeracy_no_skills' => 'Ma haysano aqoon aasaasi ah oo ku saabsan xisaabta',
    'numeracy_limited' => 'Waxaan haystaa aqoon kooban oo ku saabsan xisaabta',
    'numeracy_moderate' => 'Waxaan haystaa aqoon dhexdhexaad ah oo ku saabsan xisaabta',
    'numeracy_proficient' => 'Waxaan ku fiicanahay xisaabta',

                // Application structure
                'application_form' => 'Foomka Codsigashada',
                'scholarship_information' => 'Macluumaadka Bixinta Deeqda',
                'description' => 'Sharaxaad',
                'closing_date' => 'Taariikhda Xidhitaanka',
                'personal_information' => 'Macluumaadka Shakhsiga',
                'supporting_documents' => 'Dukumiintiyada Taageeraya',
                'next_step' => 'Tallaabada Xiga',
                'previous' => 'Hore',
                'submit_application' => 'Gudbi Codsigashada',
                'add_files' => 'Ku Dar Faylalka',
                'drag_files_here' => 'Jiid faylalka halkan ama guji si aad u raadiso',
                'select_option' => 'Xulo Ikhtiyaar',
                'optional' => 'ikhtiyaari',

                // Personal Information Fields
                'full_name' => 'Magaca Oo Dhan',
                'full_name_placeholder' => 'Geli magacaaga oo dhan',
                'gender' => 'Jinsiga',
                'phone' => 'Lambarka Taleefanka',
                'phone_placeholder' => 'Geli lambarka taleefankaaga',
                'email' => 'Ciwaanka Emailka',
                'email_placeholder' => 'Geli ciwaanka emailkaaga',
                'nationality' => 'Jinsiyadda',
                'date_of_birth' => 'Taariikhda Dhalashada',
                'date_placeholder' => 'dd/bb/ssss',
                'region' => 'Gobolka',
                'district' => 'Degmada',
                'village' => 'Tuulada',
                'village_placeholder' => 'Geli tuuladaada',
                'gender_placeholder' => 'Dooro Jinsiga',
                'region_placeholder' => 'Dooro Gobolka',
                'district_placeholder' => 'Dooro Degmada',
                'nationality_placeholder' => 'Dooro wadankaga',
                'date' => 'Taariikhda',

                // Literacy & Numeracy Section
                'literacy_numeracy_section' => 'Heerka Aqoonta Akhriska Qorista & Heerka Xisaabta',
                'literacy_level' => 'Heerka Akhriska Qorista',
                'literacy_level_placeholder' => 'Geli heerka akhriska qorista',
                'numeracy_level' => 'Heerka Xisaabta',
                'numeracy_level_placeholder' => 'Geli heerka xisaabta',
                'literacy_numeracy_qualification' => 'Aqoon Akhris-Qoris iyo Xisaab',
                'literacy_numeracy_qualification_placeholder' => 'Geli aqoonta akhriska qorista iyo xisaabta',
                'recent_education' => 'Waxbarashada Ugu Dambeysay',
                'recent_education_placeholder' => 'Geli faahfaahinta waxbarashada ugu dambeysay',

                // Disability Section
                'disability_disclosure' => 'Qeybta Sheegista Naafoonimada',
                'disability_question' => 'Ma haysaa naafoonimo ama dhibaatooyin waxbarasho aad jeclaan lahayd inaan ogaanno?',
                'disability_explanation' => 'Fadlan sharax naafoonimadaada ama dhibaatooyinka waxbarashada',
                'disability_explanation_placeholder' => 'Fadlan sharax naafoonimadaada',

                // Identity Verification
                'personal_identification' => 'Xaqiijinta Aqoonsiga Shakhsiga',
                'identity_type' => 'Nooca Aqoonsiga',
                'identity_type_placeholder' => 'Xulo nooca aqoonsiga (ikhtiyaari)',
                'identity_attachment' => 'Dukumiintiga Aqoonsiga',

                // Enrollment
                'enrollment_school' => 'Isku Qorista - Xulashada Dugsiyeenta',
                'enrollment_course' => 'Isku Qorista - Xulashada Koorsada',
                'institute' => 'Hay\'adda',
                'course' => 'koorsada',
                'course_placeholder' => 'Dooro koorsada',

                // Secondary Education
                'secondary_school_education' => 'Waxbarashada Dugsiyeenta Sare',
                'secondary_region' => 'Gobolka Dugsiga Sare',
                'secondary_school' => 'Dugsiga Sare',
                'secondary_school_placeholder' => 'Geli magaca dugsiga sare',
                'secondary_graduation_year' => 'Sannadka Qalin Jabinta',
                'secondary_graduation_year_placeholder' => 'Geli sannadka qalin jabinta',
                'secondary_grade' => 'Darajada Sare',
                'secondary_grade_placeholder' => 'Geli darajada sare',

                // Higher Education
                'higher_education' => 'Aqoonta Ugu Sarreysa',
                'highest_qualification' => 'Aqoon Ugu Sarreysa',
                'highest_qualification_placeholder' => 'Dooro Aqoontada Ugu Sarreysa',
                'highest_qualification_detail' => 'Faahfaahinta Aqoonta Ugu Sarreysa',
                'highest_qualification_detail_placeholder' => 'Geli faahfaahinta aqoonta ugu sarreysa',

                // Last Education Institution
                'last_education' => 'Hay\'adda Waxbarashada Ugu Dambeysay',
                'institution_name' => 'Magaca Hay\'adda',
                'institution_name_placeholder' => 'Geli magaca hay\'adda',
                'location' => 'Goobta',
                'location_placeholder' => 'Geli goobta',
                'start_year' => 'Sannadka Bilowga',
                'start_year_placeholder' => 'Geli sannadka bilowga',
                'end_year' => 'Sannadka Dhamaadka',
                'end_year_placeholder' => 'Geli sannadka dhamaadka',
                'qualification' => 'Aqoon',
                'qualification_placeholder' => 'Geli aqoonta',
                'minimum_grade' => 'Darajada Ugu Yar',
                'minimum_grade_placeholder' => 'Geli darajada ugu yar/GPA',

                // Supporting Documents
                'certificates_attachment' => 'Dukumiintiga Shahaadadyada',
                'select_institute_placeholder' => 'marka hore dooro Hayadda',

//Declaration Section
                'declaration' => 'Bayaanka',
                'declaration_text' => 'Waxaan si daacad ah u caddeynayaa in dhammaan macluumaadka aan ku bixiyayfoomkan codsiga uu yahay mid sax ah, dhammaystiran, oo run ah sida ugu wanaagsanee aan u ogahay. Waxaan sidoo kale muujinayaa go’aankeyga dhabta ah ee ah inaan sibuuxda uga faa’iideysto fursadda waxbarasho ee deeqdan waxbarasho. Xaaladahadhaqaale awgood, ma aanan awoodin inaan wax ka barto hay’adaha kale, sidaasdarteed waxaan si dhab ah u daneynayaa inaan ka faa’iideysto barnaamijkan.',
              

// Acknowledgment Section
                'acknowledgement_agreement' => 'Caddeyn iyo Oggolaansho',
                'i_confirm_read_agree' => 'Waxaan caddeynayaa in aan akhriyey oo aan oggolahay shuruudaha iyo xaaladahakor ku xusan.',

                // Choices
                'yes' => 'Haa',
                'no' => 'Maya',
                'prefer_not_to_say' => 'Ma rabo inaan sheego',
            ]
        ];
    }

    public function getCurrentLanguage(): string
    {
        $session = $this->requestStack->getSession();
        return $session->get('app_language', 'en');
    }

    public function setLanguage(string $language): void
    {
        $session = $this->requestStack->getSession();
        $session->set('app_language', $language);
    }

    public function trans(string $key): string
    {
        $language = $this->getCurrentLanguage();
        return $this->translations[$language][$key] ?? $key;
    }

    public function getAllTranslations(): array
    {
        $language = $this->getCurrentLanguage();
        return $this->translations[$language] ?? [];
    }
}