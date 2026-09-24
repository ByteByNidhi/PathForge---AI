# PathForge AI — WFS

> **PathForge AI: Forge your next path**

PathForge AI is a gamified career-development platform designed to help students discover suitable career paths, develop technical and professional skills, follow structured learning roadmaps, track their progress, discover opportunities, and interact with an AI-powered career assistant.

This repository contains the **WFS (Web Framework/System) implementation** of PathForge AI.

---

## Table of Contents

1. [Project Overview](#1-project-overview)
2. [Main Features](#2-main-features)
3. [System Roles](#3-system-roles)
4. [Technology Stack](#4-technology-stack)
5. [System Requirements](#5-system-requirements)
6. [Project Structure](#6-project-structure)
7. [Getting the Project](#7-getting-the-project)
8. [Installation](#8-installation)
9. [Environment Configuration](#9-environment-configuration)
10. [Database Setup](#10-database-setup)
11. [Database Structure](#11-database-structure)
12. [Seeded Project Data](#12-seeded-project-data)
13. [Running the Application](#13-running-the-application)
14. [Student Panel](#14-student-panel)
15. [Organization Panel](#15-organization-panel)
16. [Admin Panel](#16-admin-panel)
17. [Roadmaps and Progression](#17-roadmaps-and-progression)
18. [Opportunity Hub](#18-opportunity-hub)
19. [AI Studio](#19-ai-studio)
20. [Himalayas Integration](#20-himalayas-integration)
21. [Testing](#21-testing)
22. [Common Problems and Solutions](#22-common-problems-and-solutions)
23. [Important Security Notes](#23-important-security-notes)
24. [Important Instructions for Team Members](#24-important-instructions-for-team-members)
25. [Development Notes](#25-development-notes)
26. [Project Documentation](#26-project-documentation)
27. [Future Development](#27-future-development)
28. [License and Project Status](#28-license-and-project-status)

---

# 1. Project Overview

## 1.1 What is PathForge AI?

PathForge AI is a career-development platform that combines career planning, skill development, learning roadmaps, opportunity discovery, progress tracking, achievements, and artificial intelligence into one system.

The idea is to make career development more structured and engaging by treating the student's career journey like a progression system.

Students can:

- Select a career path.
- Select their skills.
- Follow a learning roadmap.
- Complete roadmap steps.
- Earn XP.
- Level up.
- Unlock achievements.
- Discover internships, scholarships, hackathons, research opportunities, and jobs.
- Check how well opportunities match their skills.
- Save opportunities for later.
- Use AI-powered career assistance.
- Request career paths.
- Manage their profile and skills.

The system also provides separate functionality for organizations and administrators.

---

## 1.2 WFS Implementation

This project is the **WFS implementation** of PathForge AI.

The WFS version is built using:

- PHP
- Laravel
- MySQL
- Blade
- HTML
- CSS
- JavaScript
- Vite

The project also integrates external services for AI functionality and opportunity ingestion.

---

# 2. Main Features

## 2.1 Authentication

The system provides:

- User registration
- User login
- User logout
- Authentication-protected areas

---

## 2.2 Student Onboarding

New users complete an onboarding process where they can:

- Select a career path.
- Select relevant skills.
- Configure their starting profile.
- Confirm their selections.

The onboarding process determines the initial career direction and skill profile of the student.

---

## 2.3 Career Paths

The project currently contains the following career paths:

- Cybersecurity
- Web Development
- Data Science
- AI & Machine Learning
- Cloud & DevOps
- Mobile Development
- UI/UX Design

Each career path can have:

- A name
- A description
- An icon
- Associated skills
- A roadmap

---

## 2.4 Skill Management

Students can:

- Select skills during onboarding.
- View their current skills.
- Manage skills through their profile.
- Remove skills from their profile.

The system maintains user-skill relationships in the database.

---

## 2.5 Learning Roadmaps

Students can:

- View their selected career path.
- View the corresponding roadmap.
- View roadmap steps.
- Complete roadmap steps.
- Earn XP from completed steps.
- Track their progress.

The system supports both curated roadmaps and AI-generated roadmap functionality.

---

## 2.6 XP and Levels

The project contains a progression system based on XP.

When a student completes eligible activities, XP can be awarded.

The user's level is calculated by the application's progression logic.

Progress is stored in the database so that it persists between sessions.

---

## 2.7 Achievements

The system contains an achievement system that rewards students for reaching specific progression milestones.

The seeded achievements include:

1. PATH IGNITED
2. TRAILBLAZER
3. SUMMIT SEEKER
4. SKILLFORGED
5. XP OVERDRIVE
6. ASCENDANT

Achievements are stored and associated with users through the user-achievement relationship.

---

## 2.8 Opportunity Hub

The Opportunity Hub allows students to discover opportunities such as:

- Hackathons
- Internships
- Scholarships
- Research opportunities
- Jobs
- Other career-related opportunities

Students can:

- Browse opportunities.
- Search opportunities.
- Filter opportunities.
- Sort opportunities.
- Open opportunity details.
- View required skills.
- View skill matching.
- Save opportunities.
- Remove saved opportunities.
- Open the external application URL.

---

## 2.9 Skill Matching

The system can calculate a student's skill match against the skills required by an opportunity.

The matching system considers the student's stored skills and the opportunity's associated skills.

If appropriate skill information is unavailable, the system can return no percentage rather than presenting an inaccurate match.

---

## 2.10 Saved Opportunities

Students can save opportunities for later.

Saved opportunities are stored separately for each user.

Students can:

- Save an opportunity.
- View saved opportunities.
- Remove a saved opportunity.

---

## 2.11 AI Studio

AI Studio provides AI-powered career assistance.

It can use relevant student context such as:

- Selected career path
- Skills
- Progress
- Career-related information

The AI functionality uses the Google Gemini API.

---

## 2.12 AI Roadmap Generation

Administrators can generate roadmap drafts using AI.

The AI-generated roadmap functionality validates the generated structure before storing it.

Generated roadmaps can be:

- Generated
- Saved as drafts
- Previewed
- Published

The system uses the application's available skill catalogue when generating roadmap content.

---

## 2.13 Organization Panel

Organizations can:

- Manage organization information.
- Manage organization members.
- Create opportunities.
- Edit opportunities.
- Delete opportunities.
- Submit opportunities for approval.
- View their organization opportunities.

Organizations cannot approve their own opportunities.

---

## 2.14 Admin Panel

Administrators can manage major parts of the platform, including:

- Opportunities
- Organizations
- Users
- Career path requests
- Roadmaps
- Roadmap steps
- AI-generated roadmap drafts

Administrators can also import opportunities from the Himalayas Jobs API.

---

# 3. System Roles

The application contains three primary functional roles.

---

## 3.1 Student

Students can access:

- Dashboard
- Roadmaps
- Opportunity Hub
- Saved Opportunities
- Achievements
- AI Studio
- Profile

Students can also:

- Complete onboarding.
- Select a career path.
- Manage skills.
- Complete roadmap steps.
- Earn XP.
- Unlock achievements.
- Search opportunities.
- Save opportunities.
- Use AI features.
- Request career paths.

---

## 3.2 Organization User

Organization users can access the organization functionality according to their membership and role.

Organization functionality includes:

- Organization profile
- Organization members
- Opportunity management
- Opportunity submission

Organization membership is controlled through the organization membership system.

Organization roles include:

- Owner
- Member

---

## 3.3 Administrator

Administrators have access to administrative functionality.

Administrative capabilities include:

- Opportunity management
- Opportunity approval/rejection
- Opportunity import
- Organization management
- User management
- Career path request review
- Roadmap management
- Roadmap step management
- AI roadmap generation

Administrator access is controlled using the application's user administrator status.

---

# 4. Technology Stack

## Backend

| Technology | Purpose |
|---|---|
| PHP 8.2+ | Server-side programming |
| Laravel 11 | Web application framework |
| Eloquent ORM | Database interaction |
| MySQL | Relational database |

## Frontend

| Technology | Purpose |
|---|---|
| Blade | Server-side templating |
| HTML | Page structure |
| CSS | Styling |
| JavaScript | Client-side interaction |
| Vite | Asset development/build system |

## External Services

| Service | Purpose |
|---|---|
| Google Gemini API | AI-powered functionality |
| Himalayas Jobs API | External job opportunity ingestion |

## Development Tools

| Tool | Purpose |
|---|---|
| XAMPP | Local PHP/MySQL environment |
| phpMyAdmin | MySQL database management |
| Composer | PHP dependency management |
| npm | Frontend dependency management |
| Git | Version control |
| GitHub | Source-code repository |

---

# 5. System Requirements

Before running the project, install the following software.

### Required

- PHP 8.2 or later
- Composer
- MySQL
- Node.js
- npm
- Git

### Recommended

- XAMPP
- phpMyAdmin
- Visual Studio Code

---

## 5.1 Verify PHP

Run:

```bash
php -v

The project requires PHP 8.2 or higher.

5.2 Verify Composer

Run:

composer -V
5.3 Verify Node.js

Run:

node -v
5.4 Verify npm

Run:

npm -v
6. Project Structure

The main project structure is approximately:

PathForge - AI/
│
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   ├── Middleware/
│   │   └── Requests/
│   │
│   ├── Models/
│   ├── Policies/
│   └── Services/
│
├── bootstrap/
│
├── config/
│
├── database/
│   ├── factories/
│   ├── migrations/
│   └── seeders/
│
├── public/
│
├── resources/
│   ├── css/
│   ├── js/
│   └── views/
│
├── routes/
│   └── web.php
│
├── storage/
│
├── tests/
│   ├── Feature/
│   └── Unit/
│
├── artisan
├── composer.json
├── package.json
├── vite.config.js
├── .env.example
└── README.md
7. Getting the Project

The project can be obtained either by cloning the GitHub repository or by downloading the repository as a ZIP file.

7.1 Clone Using Git

Run:

git clone YOUR_GITHUB_REPOSITORY_URL

Then enter the project folder:

cd "PathForge - AI"

Replace YOUR_GITHUB_REPOSITORY_URL with the actual repository URL.

7.2 Download as ZIP

If Git is not available:

Open the GitHub repository.
Click Code.
Select Download ZIP.
Extract the ZIP.
Open the extracted project folder in Visual Studio Code or a terminal.
8. Installation

Follow these steps in order.

Step 1 — Open the Project

Open a terminal inside the project directory.

Example:

cd "PathForge - AI"
Step 2 — Install PHP Dependencies

Run:

composer install

This installs Laravel and the PHP packages required by the project.

Step 3 — Create the Environment File

The .env file should not be committed to GitHub.

Create a local .env file from .env.example.

Windows Command Prompt
copy .env.example .env
PowerShell
Copy-Item .env.example .env

You can also manually copy .env.example and rename the copy to .env.

Step 4 — Generate the Laravel Application Key

Run:

php artisan key:generate
Step 5 — Configure MySQL

Start MySQL using XAMPP or another MySQL installation.

Open the .env file and configure:

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=pathforge
DB_USERNAME=root
DB_PASSWORD=

If your MySQL installation has a password, enter it in DB_PASSWORD.

For example:

DB_PASSWORD=your_mysql_password

Do not copy someone else's database password.

9. Database Setup

The project uses a MySQL database named:

pathforge
Step 1 — Start MySQL

If using XAMPP:

Open XAMPP Control Panel.
Start Apache.
Start MySQL.
Step 2 — Create the Database

Open phpMyAdmin.

Create a new database named:

pathforge

The name should match:

DB_DATABASE=pathforge
Step 3 — Run Migrations and Seeders

From the project directory, run:

php artisan migrate --seed

This will:

Create the required database tables.
Insert the initial project/demo data.

No copy of the developer's physical MySQL database is required.

Every team member can have their own local pathforge database.

10. Database Structure

The project uses MySQL.

The current migration set creates 25 tables.

10.1 Laravel/System Tables

The Laravel/system tables are:

users
password_reset_tokens
sessions
cache
cache_locks
jobs
job_batches
failed_jobs
10.2 PathForge Tables

The PathForge/domain tables are:

learning_paths
roadmap_steps
skills
user_skills
user_progress
opportunities
opportunity_skills
saved_opportunities
connections
admins
achievements
user_achievements
learning_path_skill
roadmap_step_skill
organizations
organization_users
career_path_requests
10.3 Important Note About admins

The admins table exists in the database schema, but current administrator authentication is handled through the users.is_admin field.

Therefore, the admins table should not be interpreted as the current primary administrator authentication mechanism.

10.4 Important Note About connections

The connections table exists in the database schema, but it does not represent a currently exposed primary live application feature.

It should therefore not be presented in documentation as an active student-facing feature unless the implementation is changed later.

11. Seeded Project Data

Running:

php artisan migrate --seed

creates the project's initial/demo data.

11.1 Learning Paths

The project contains seven seeded learning paths:

Cybersecurity
Web Development
Data Science
AI & Machine Learning
Cloud & DevOps
Mobile Development
UI/UX Design
11.2 Roadmap Steps

The project contains:

140 roadmap steps

The curated roadmap data contains 20 steps for each of the seven main learning paths.

11.3 Skills

The project contains the following main skill catalogue:

HTML
CSS
JavaScript
Python
PHP
Laravel
React
MySQL
Cybersecurity
Git
Communication
UI/UX Design
Linux
SQL
Docker
Figma
Networking
11.4 Achievements

The project contains six seeded achievements:

PATH IGNITED
TRAILBLAZER
SUMMIT SEEKER
SKILLFORGED
XP OVERDRIVE
ASCENDANT
11.5 Opportunities

The project contains seeded demonstration opportunities including:

Smart India Hackathon 2026
NASA Space Apps Challenge 2026
ETHIndia Hackathon
Google Summer of Code
Microsoft Research Internship (India)
ISRO Internship Programme
Generation Google Scholarship (APAC)
Inlaks Shivdasani Scholarship
MIT Undergraduate Research Opportunities Program
CERN Summer Student Programme

These are demonstration/seed data and should not automatically be treated as currently available opportunities.

12. Running the Application

There are two main processes involved in running the application locally:

Laravel
Vite/frontend assets
12.1 Build Frontend Assets

For a completed local demonstration, run:

npm install

Then:

npm run build
12.2 Start Laravel

Run:

php artisan serve

The application will normally be available at:

http://127.0.0.1:8000

Open the address in a web browser.

12.3 Development Mode

If actively modifying frontend assets, run:

npm run dev

Keep the Vite development process running.

In another terminal, run:

php artisan serve
13. Student Panel

The student-facing application contains the following main navigation:

Dashboard
Roadmaps
Opportunity Hub
Saved
Achievements
AI Studio
Profile
Logout
13.1 Dashboard

The dashboard provides an overview of the student's career-development progress.

It can include information such as:

Selected career path
Skills
XP
Level
Roadmap progress
Achievements
Relevant opportunities
13.2 Roadmaps

Students can access their career roadmap and work through its steps.

Roadmap functionality includes:

Viewing roadmap steps
Viewing step information
Completing steps
Receiving XP
Tracking progress
13.3 Opportunity Hub

Students can browse approved opportunities.

Available functionality includes:

Search
Filters
Sorting
Opportunity details
Skill matching
Save/unsave
External application links
13.4 Saved Opportunities

Students can access their saved opportunities separately.

13.5 Achievements

Students can view achievements they have unlocked through the progression system.

13.6 AI Studio

Students can interact with the AI career assistant.

AI functionality depends on the Gemini API configuration.

13.7 Profile

Students can view and manage their profile and skills.

14. Organization Panel

The organization system allows organizations to manage their presence and opportunities.

14.1 Organization Profile

Organization users can manage information such as:

Organization name
Contact information
Website
Description
Other organization information supported by the application
14.2 Organization Members

Organization owners can manage organization membership.

Membership roles include:

Owner
Member
14.3 Organization Opportunities

Organization users can create and manage opportunities.

The opportunity workflow can include:

Draft
   ↓
Pending
   ↓
Admin Review
   ↓
Approved

or:

Pending
   ↓
Rejected

An organization cannot approve its own opportunity.

15. Admin Panel

The administrator panel provides management and moderation functionality.

15.1 Admin Dashboard

The administrator can access the administrative dashboard and manage major system entities.

15.2 Opportunity Management

Administrators can:

View opportunities
Create opportunities
Edit opportunities
Delete opportunities
Approve opportunities
Reject opportunities
Review submitted opportunities
Import external opportunities
15.3 Organization Management

Administrators can:

View organizations
Create organizations
Manage organization information
15.4 User Management

Administrators can:

View users
View user information
15.5 Career Path Requests

Students can request career paths.

Administrators can:

View requests
Review requests
Process requests
15.6 Roadmap Management

Administrators can:

View roadmaps
Create roadmap steps
Edit roadmap steps
Delete roadmap steps
Manage roadmap ordering
Generate AI roadmap drafts
Preview roadmaps
Publish roadmaps
16. Roadmaps and Progression

The roadmap system is one of the core parts of PathForge AI.

16.1 Curated Roadmaps

The project includes curated roadmap data.

The current master roadmap data contains:

7 learning paths
20 steps per path
140 total roadmap steps
16.2 Roadmap Steps

Each roadmap step can contain:

Step number
Title
Description
XP reward
Associated skills
Publication status
16.3 Progress Tracking

A student's roadmap progress is stored in the user_progress table.

Progress records include:

User
Roadmap step
Status
Completion timestamp
16.4 XP

When an eligible roadmap step is completed, the progression system can award XP.

The user's XP and level are stored with the user account.

16.5 Level Calculation

The application's user progression logic calculates the user's level based on XP.

The progression logic is implemented within the project's application code and should not be manually duplicated elsewhere.

17. Opportunity Hub

The Opportunity Hub is responsible for career-related opportunity discovery.

17.1 Opportunity Sources

Opportunities can originate from:

Seeded/project data
Organizations
Himalayas
17.2 Opportunity Approval

Opportunities have an approval state.

Possible approval states include:

draft
pending
approved
rejected

Student-facing visibility is controlled by the application's approval and visibility rules.

17.3 Opportunity Status

Opportunity availability can also use statuses such as:

open
closing_soon
closed

The application determines the appropriate opportunity status using the deadline.

17.4 Closing Soon

An opportunity is considered closing_soon when its deadline is within the application's configured closing-soon period.

The current implementation uses a 14-day threshold.

17.5 External Applications

PathForge AI does not contain an internal application-submission system.

When a student chooses to apply, the system uses the opportunity's external application URL.

18. AI Studio

PathForge AI uses Google's Gemini API for AI functionality.

The project uses the following environment configuration:

GEMINI_MODEL=gemini-3.6-flash

A valid API key must be supplied locally:

GEMINI_API_KEY=YOUR_API_KEY

Do not commit the API key to GitHub.

18.1 AI Career Assistance

AI Studio can use relevant student information such as:

Career path
Skills
Progress
Career-development context

The AI response is generated using the configured Gemini model.

18.2 Chat History

The AI Studio implementation uses a limited amount of recent chat context when constructing AI requests.

The current implementation uses up to eight messages of chat history.

19. AI Roadmap Generation

Administrators can use AI to generate roadmap drafts.

The roadmap generation service validates generated roadmap data before saving it.

19.1 Roadmap Generation Rules

Generated roadmap steps are expected to contain:

Title
Description
XP reward
Associated skills

XP values are validated against the application's allowed range.

The generated roadmap uses skills from the project's skill catalogue.

19.2 Draft Generation

AI-generated roadmaps are initially stored as unpublished drafts.

They can be:

Generated
Validated
Saved as a draft
Previewed
Published by an administrator
19.3 Publishing

Publishing a roadmap replaces the current published roadmap according to the application's publishing rules.

Publishing can be restricted when existing live student progress would be affected.

20. Himalayas Integration

PathForge AI can retrieve job opportunities through the Himalayas Jobs API.

The configured base endpoint is:

https://himalayas.app/jobs/api/search
20.1 Administrator Access

Himalayas importing is available through administrator functionality.

Students do not directly control the external import process.

20.2 Imported Opportunity Handling

Imported opportunities are normalized into the application's opportunity structure.

Important information can include:

Title
Organization
Description
Type
Location
Application URL
Deadline
Eligibility
External identifier
Source
20.3 Approval

New imported opportunities are not automatically treated as approved student opportunities.

They enter the appropriate approval workflow.

20.4 External API Requirements

An internet connection is required when using the Himalayas integration.

The integration also handles external API problems such as:

Connection failures
Timeouts
Rate limiting
Server errors
Invalid JSON responses
21. Testing

The project contains automated tests covering major application functionality.

Tests are located under:

tests/

The project includes Feature and Unit tests.

21.1 Major Tested Areas

Tests cover functionality including:

Authentication and onboarding
Profile skills
Student progression
Achievements
Opportunity Hub
Student opportunity functionality
Organization panel
Admin panel
AI Studio
Dynamic AI roadmaps
Himalayas opportunity ingestion
21.2 Running Tests

Run:

php artisan test

For compact output:

php artisan test --compact
21.3 Test Environment

Tests should be run in a properly configured PHP environment with all required PHP extensions enabled.

If tests fail because of missing PHP extensions, install/enable the required extensions and run the test suite again.

Do not treat an environment configuration problem as proof that the application functionality itself is incorrect.

22. Common Problems and Solutions
22.1 composer is not recognized

Install Composer and make sure it has been added to the system PATH.

Verify with:

composer -V
22.2 php is not recognized

Make sure PHP is installed and available through the system PATH.

If using XAMPP, verify the PHP installation inside the XAMPP directory.

Run:

php -v
22.3 Database Connection Error

Check:

MySQL is running.
The pathforge database exists.
.env contains the correct database name.
The username is correct.
The password is correct.
MySQL is running on the configured port.

Example:

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=pathforge
DB_USERNAME=root
DB_PASSWORD=
22.4 Tables Do Not Exist

Run:

php artisan migrate --seed

If the database already contains an incorrect/old project schema and this is a disposable local development database, you can recreate it using:

php artisan migrate:fresh --seed

Warning: migrate:fresh deletes existing tables and data in the configured database.

Do not run it against a database containing data you need to keep.

22.5 .env Changes Are Not Taking Effect

Run:

php artisan config:clear
php artisan cache:clear

Then restart the Laravel server.

22.6 Missing Application Key

Run:

php artisan key:generate
22.7 Missing PHP Dependencies

Run:

composer install
22.8 Missing Node Dependencies

Run:

npm install
22.9 Frontend Assets Are Not Loading

Try:

npm run build

Then restart:

php artisan serve

During active frontend development, use:

npm run dev
22.10 AI Features Are Not Working

Check:

GEMINI_API_KEY=YOUR_API_KEY

Make sure:

The API key is valid.
The .env file contains the key.
The internet connection is working.
The Gemini configuration is correct.

Then run:

php artisan config:clear
22.11 Himalayas Import Is Not Working

Check:

Internet connection
External API availability
Administrator access
Application logs
API response/rate-limit errors

The Himalayas functionality depends on the external service being available.

23. Important Security Notes
23.1 Never Commit .env

The .env file contains environment-specific configuration and may contain secrets.

Do not upload it to GitHub.

Use:

.env.example

as the configuration template.

23.2 Never Commit API Keys

Do not put the Gemini API key directly into:

PHP source files
Blade files
JavaScript files
GitHub README
Screenshots
Public documentation

Use:

GEMINI_API_KEY=YOUR_API_KEY

inside the local .env.

23.3 Do Not Share Private Credentials Publicly

Demo/admin credentials should be provided privately when necessary.

Do not publish passwords in the public repository README.

24. Important Instructions for Team Members

This repository is being shared so that team members can:

Run the complete WFS project locally.
Understand the project.
Access the Student panel.
Access the Organization panel.
Access the Admin panel.
Test existing functionality.
Take screenshots.
Prepare project documentation.
Prepare diagrams.
Review the existing implementation.
24.1 Do Not Modify the Main Repository

Team members should not push changes to the main repository unless explicitly instructed.

Do not:

Delete project files.
Modify migrations.
Modify seeders.
Modify application logic.
Change routes.
Replace database structures.
Push experimental changes to the main branch.

If experimentation is required, create a separate local copy or branch.

24.2 Each Team Member Uses Their Own Database

Every team member should create their own local MySQL database:

pathforge

The database is created and populated using:

php artisan migrate --seed

The project does not depend on the developer's personal computer or personal MySQL server.

24.3 The Developer's Computer Does Not Need to Be Running

Once the project has been cloned and configured correctly, the project runs locally on each team member's computer.

For example:

Team Member's Computer
│
├── Laravel
├── PHP
├── MySQL
├── Project Files
└── Local Database

The original developer's laptop does not need to remain switched on.

25. Development Notes
25.1 Environment-Specific Configuration

Every developer should have their own:

.env

The .env file should not be shared through Git.

25.2 Database Reproducibility

The project uses Laravel migrations and seeders to reproduce the database structure and initial data.

The recommended setup is:

php artisan migrate --seed

rather than connecting to another developer's database.

25.3 External Services

Some functionality depends on external services.

Gemini

Used for:

AI Studio
AI-assisted career functionality
AI roadmap generation
Himalayas

Used for:

External job opportunity ingestion

These services may require internet connectivity and valid configuration.

26. Project Documentation

The project documentation should cover the following areas:

Introduction
Project profile
Project introduction
Environment description
Hardware/software requirements
Tools and technologies
Proposed system
Scope
Aim and objectives
Expected advantages
Use-case diagram
Database diagram
Table structures
Screen layouts
System testing
Test cases
System limitations
Future enhancements
References

The actual application should be used when taking screenshots for the final documentation.

27. Future Development

Potential future enhancements may include:

More career paths
More learning resources
More opportunity sources
Expanded AI career guidance
Advanced skill analysis
More detailed analytics
Improved recommendation systems
Additional organization functionality
Internal opportunity application tracking
Production-ready authentication and deployment
Real subscription/payment integration
Additional collaboration features

These are future possibilities and are not necessarily implemented in the current WFS version.

28. Project Status

The current WFS implementation contains:

Student functionality
Organization functionality
Administrative functionality
Career paths
Skills
Roadmaps
Progress tracking
XP and levels
Achievements
Opportunity Hub
Saved opportunities
AI Studio
AI roadmap generation
Organization opportunity workflow
Admin opportunity management
Himalayas opportunity ingestion

The project is intended to be run locally for development, demonstration, testing, and academic evaluation.
