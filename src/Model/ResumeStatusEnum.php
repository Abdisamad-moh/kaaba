<?php

namespace App\Model;

enum ResumeStatusEnum: string
{
    case OPEN_TO_WORK = 'Open to Work';
    case SELF_EMPLOYED = 'Self-Employed';
    case EMPLOYED = 'Employed';
    case FREELANCER = 'Freelancer';
    case CONTRACTOR = 'Contractor';
    case REMOTE_WORKER = 'Remote Worker';
    case CONSULTANT = 'Consultant';
    case RETIRED = 'Retired';
    case STUDENT =  'Student';
    case VOLUNTEER = 'Volunteer';
    case SEEKING_INTERNSHIP = 'Seeking Internship';
    case SEASONAL_WORKER = 'Seasonal Worker';
}