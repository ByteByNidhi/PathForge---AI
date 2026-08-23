<?php

namespace Database\Seeders;

use App\Models\Opportunity;
use Illuminate\Database\Seeder;

class OpportunitySeeder extends Seeder
{
    public function run(): void
    {
        $opportunities = [
            [
                'title' => 'Smart India Hackathon 2026',
                'organization' => 'AICTE & Ministry of Education',
                'type' => 'Hackathon',
                'description' => 'A nationwide hackathon where student teams solve real problem statements from government ministries and industry partners in software and hardware tracks.',
                'required_skills' => 'Web or mobile development, Python, teamwork, problem solving',
                'eligibility' => 'Teams of 6 students enrolled in a recognized Indian college or university. Each team must include at least one female member.',
                'deadline' => '2026-10-15',
                'application_url' => 'https://www.sih.gov.in/',
                'location' => 'India (online screening, on-site finals)',
            ],
            [
                'title' => 'NASA Space Apps Challenge 2026',
                'organization' => 'NASA',
                'type' => 'Hackathon',
                'description' => 'A global hackathon that uses open NASA data to build solutions around Earth science, space exploration, and climate challenges over a 48-hour weekend.',
                'required_skills' => 'Data analysis, APIs, design, storytelling, any programming language',
                'eligibility' => 'Open to students and professionals worldwide. Teams of 1–6 people. Participants must be at least 13 years old.',
                'deadline' => '2026-09-30',
                'application_url' => 'https://www.spaceappschallenge.org/',
                'location' => 'Global (local sites and virtual)',
            ],
            [
                'title' => 'ETHIndia Hackathon',
                'organization' => 'ETHGlobal',
                'type' => 'Hackathon',
                'description' => 'A three-day Ethereum hackathon for builders working on wallets, DeFi, identity, and public goods. Mentors from leading web3 teams support projects through demo day.',
                'required_skills' => 'Solidity, JavaScript, smart contracts, product design',
                'eligibility' => 'Open to developers, designers, and students 18+. Prior blockchain experience is helpful but not required.',
                'deadline' => '2026-11-08',
                'application_url' => 'https://ethglobal.com/events',
                'location' => 'Bengaluru, India',
            ],
            [
                'title' => 'Google Summer of Code',
                'organization' => 'Google Open Source',
                'type' => 'Internship',
                'description' => 'A paid remote internship where contributors work with open-source organizations on a defined coding project with dedicated mentors.',
                'required_skills' => 'Git, open-source contribution, language depends on the chosen organization',
                'eligibility' => 'Students and beginner contributors 18+ who are new to the selected open-source organization. Must be eligible to work in their country of residence.',
                'deadline' => '2026-04-08',
                'application_url' => 'https://summerofcode.withgoogle.com/',
                'location' => 'Remote',
            ],
            [
                'title' => 'Microsoft Research Internship (India)',
                'organization' => 'Microsoft Research',
                'type' => 'Internship',
                'description' => 'A research internship with MSR India teams working on machine learning, systems, HCI, and sociotechnical systems. Interns publish, prototype, and present findings.',
                'required_skills' => 'Python, machine learning, research writing, experimental design',
                'eligibility' => 'Enrolled in a Bachelor’s, Master’s, or PhD program in CS or a related field. Strong academic record and interest in research.',
                'deadline' => '2026-12-01',
                'application_url' => 'https://www.microsoft.com/en-us/research/academic-program/internships-msr-india/',
                'location' => 'Bengaluru, India',
            ],
            [
                'title' => 'ISRO Internship Programme',
                'organization' => 'Indian Space Research Organisation',
                'type' => 'Internship',
                'description' => 'Short-term internships at ISRO centres for students to work on space science, electronics, remote sensing, and mission support projects.',
                'required_skills' => 'Engineering fundamentals, MATLAB or Python, technical report writing',
                'eligibility' => 'Indian students in the pre-final or final year of UG/PG engineering or science programmes, with at least 60% or 6.32 CGPA.',
                'deadline' => '2026-09-15',
                'application_url' => 'https://www.isro.gov.in/InternshipAndProjects.html',
                'location' => 'ISRO centres across India',
            ],
            [
                'title' => 'Generation Google Scholarship (APAC)',
                'organization' => 'Google',
                'type' => 'Scholarship',
                'description' => 'Financial support and community programming for students pursuing computer science who demonstrate academic excellence and a commitment to diversity in tech.',
                'required_skills' => 'Computer science coursework, leadership, community impact',
                'eligibility' => 'Women and students from underrepresented groups enrolled in a bachelor’s programme in CS or a closely related field in the Asia-Pacific region.',
                'deadline' => '2026-10-31',
                'application_url' => 'https://buildyourfuture.withgoogle.com/scholarships',
                'location' => 'Asia-Pacific (study in-region)',
            ],
            [
                'title' => 'Inlaks Shivdasani Scholarship',
                'organization' => 'Inlaks Shivdasani Foundation',
                'type' => 'Scholarship',
                'description' => 'Scholarships for outstanding Indian students to pursue full-time Master’s, MPhil, or doctoral study at top universities in the US, UK, and Europe.',
                'required_skills' => 'Strong academic record, research proposal or portfolio, clear career plan',
                'eligibility' => 'Indian citizens under 30 with a first-class undergraduate degree. Applicants should have been admitted to or applied to an eligible overseas programme.',
                'deadline' => '2027-03-31',
                'application_url' => 'https://www.inlaksfoundation.org/scholarships/',
                'location' => 'United States, United Kingdom, and Europe',
            ],
            [
                'title' => 'MIT Undergraduate Research Opportunities Program',
                'organization' => 'Massachusetts Institute of Technology',
                'type' => 'Research',
                'description' => 'A structured programme that places undergraduates in faculty research groups across science, engineering, and computing for credit, pay, or volunteer work.',
                'required_skills' => 'Depends on lab: programming, lab methods, statistics, scientific writing',
                'eligibility' => 'MIT undergraduates in good academic standing. Visiting students may apply only through designated exchange channels.',
                'deadline' => '2026-09-05',
                'application_url' => 'https://urop.mit.edu/',
                'location' => 'Cambridge, MA, USA',
            ],
            [
                'title' => 'CERN Summer Student Programme',
                'organization' => 'CERN',
                'type' => 'Research',
                'description' => 'An 8–13 week research stay at CERN where students join experiments in physics, computing, engineering, and data science while attending a lecture series.',
                'required_skills' => 'Physics or CS coursework, programming, Linux, curiosity about particle physics',
                'eligibility' => 'Bachelor’s or Master’s students (not PhD) who have completed at least three years of full-time studies in physics, computing, or engineering. Must be 18+.',
                'deadline' => '2027-01-31',
                'application_url' => 'https://careers.cern/summer',
                'location' => 'Geneva, Switzerland',
            ],
        ];

        foreach ($opportunities as $opportunity) {
            Opportunity::query()->updateOrCreate(
                ['title' => $opportunity['title']],
                $opportunity
            );
        }
    }
}
