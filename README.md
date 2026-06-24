<div align="center">

# 📝 Automated Exam Generation System

### For Educational Institutions

A web-based platform that lets university instructors build a structured question bank and generate professional, randomized PDF exams — complete with automatic answer keys and full LaTeX support for scientific and mathematical content.

<br>

![PHP](https://img.shields.io/badge/PHP-8.x-777BB4?logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-8.0-4479A1?logo=mysql&logoColor=white)
![JavaScript](https://img.shields.io/badge/JavaScript-ES6+-F7DF1E?logo=javascript&logoColor=black)
![LaTeX](https://img.shields.io/badge/LaTeX-MathJax-008080?logo=latex&logoColor=white)
![Architecture](https://img.shields.io/badge/Architecture-MVC-orange)
![Status](https://img.shields.io/badge/status-academic%20project-success)

*Fourth-Year Pre-Graduate Project — Faculty of Informatics Engineering, University of Aleppo (2025)*

</div>

---

## 📌 Overview

Preparing university exams is still largely manual in many institutions: it is time-consuming, error-prone, and makes it hard to keep multiple exam versions balanced or to deter cheating.

This project addresses that gap with a digital platform where instructors can enter, organize, and classify questions by subject, type (multiple choice, true/false), and difficulty level — then generate up to **four randomized exam versions** from a single source, each with an automatically produced answer key. Scientific and mathematical questions are rendered with high precision through LaTeX integration, making the system well suited to engineering and science courses.

The goal is not only to simplify exam preparation, but to raise the quality and integrity of academic assessment while letting instructors focus on teaching rather than routine work.

---

## ✨ Key Features

- **Question Bank** — Create, edit, and organize questions per subject, with difficulty levels, point values, and grouping.
- **Randomized Exam Generation** — Produce multiple distinct exam versions from one model using shuffling algorithms, improving exam integrity.
- **Automatic Answer Keys** — Each generated exam ships with its corresponding grading key.
- **LaTeX / Math Rendering** — Equations and scientific notation rendered accurately in the browser (MathJax/KaTeX) and in the exported PDF.
- **Professional PDF Export** — Three selectable layout templates (default, two-column, two-column with separator) with live preview.
- **Subject Management** — Per-instructor subjects with total marks and exam duration.
- **Dashboard & Analytics** — Quick stats (questions, exams, subjects), recent activity feed, and upcoming-exam scheduling.
- **Secure Authentication** — Registration with email verification and optional two-step (email PIN) login.
- **Activity Logging** — Detailed audit trail of logins and key actions for monitoring and security review.
- **Progressive Web App (PWA)** — Installable, works offline for cached resources, auto-updating.

---

## 🛠️ Tech Stack

| Layer | Technologies |
|-------|--------------|
| **Frontend** | HTML5, CSS3 (Flexbox/Grid, responsive), JavaScript (ES6+) |
| **Backend** | PHP (server-side logic, sessions, request handling) |
| **Database** | MySQL (relational, InnoDB), administered via phpMyAdmin |
| **Math Rendering** | LaTeX via MathJax / KaTeX |
| **Email** | PHPMailer (SMTP) managed through Composer |
| **Dev Environment** | XAMPP (Apache + MySQL + PHP), Visual Studio Code |
| **Pattern** | Model–View–Controller (MVC) |

---

## 🏗️ System Architecture

The application follows the **MVC** pattern to separate business logic, presentation, and data access:

- **Model** — MySQL schema and PHP data layer handling all CRUD operations.
- **View** — HTML/CSS/JavaScript interfaces for designing and previewing exams.
- **Controller** — PHP layer mediating between user input, the data layer, and the final rendered output.

This separation reduces coupling, simplifies maintenance, and leaves room for future expansion without breaking the existing structure.

---

## 🗄️ Database Schema

The system is built around the following core entities and relationships:

| Entity | Purpose |
|--------|---------|
| `university` | Stores available universities (English & Arabic names). |
| `professor` | Core entity — instructor profile, authentication, verification, token balance. |
| `subject` | Subjects taught by an instructor at a given university (total mark, duration). |
| `exam` | Generated exam metadata (subject, dates, PDF path). |
| `question` | The question bank — text, image, difficulty, up to 5 options with correctness flags, grouping, marks. |
| `token_transactions` | Tracks token purchases for the business model (status: pending / completed / failed). |
| `professor_university` | Junction table for the many-to-many instructor ↔ university relationship. |
| `exam_question` | Junction table for the many-to-many exam ↔ question relationship, with ordering. |

**Key relationships:** a university has many subjects/exams; an instructor authors many questions and exams; instructors and universities are many-to-many; exams and questions are many-to-many.

> The full Entity-Relationship Diagram (ERD) is available in the [thesis document](#-documentation).

---

## 📸 Screenshots

| Login Page | Main Dashboard |
|:----------:|:--------------:|
| ![Login Page](docs/screenshots/Login-Page.png) | ![Main Dashboard](docs/screenshots/Main-Dashboard.png) |
 
| Create Exam | My Subjects |
|:-----------:|:-----------:|
| ![Create Exam](docs/screenshots/Create-Exam.png) | ![My Subjects](docs/screenshots/My-Subjects.png) |
 
| View Exams |
|:----------:|
| ![View Exams](docs/screenshots/View-Exams.png) |

---

## 🚀 Installation & Setup

> Adjust to match your actual repository structure.

### Prerequisites
- [XAMPP](https://www.apachefriends.org/) (Apache + MySQL + PHP) or an equivalent LAMP/WAMP stack
- [Composer](https://getcomposer.org/) (for PHPMailer and other dependencies)

### Steps

```bash
# 1. Clone the repository into your web root (e.g. xampp/htdocs)
git clone https://github.com/<your-username>/<repo-name>.git
cd <repo-name>

# 2. Install PHP dependencies
composer install

# 3. Start Apache and MySQL from the XAMPP control panel
```

```sql
-- 4. Create the database and import the schema
CREATE DATABASE exam_generator_db;
-- then import the provided .sql file via phpMyAdmin or:
-- mysql -u root exam_generator_db < database/exam_generator_db.sql
```

```bash
# 5. Configure your environment
#    - Set DB credentials in the config file
#    - Set SMTP credentials for PHPMailer (email verification)
```

Then visit `http://localhost/<repo-name>/` in your browser.

> **Security note:** XAMPP ships with relaxed defaults intended for local development only. Harden the configuration (passwords, exposed ports) before any networked or production use. Never commit real API keys, SMTP passwords, or database credentials — use environment variables or an ignored config file.

---

## 🔐 Security Features

- Server-side input validation
- Protection against SQL injection
- Password hashing with strong algorithms
- Secure, encrypted session management
- Email-based verification on registration
- Optional two-step verification (email PIN) at login
- System-wide activity & login logging for auditability

---

## 🗺️ Roadmap

Planned enhancements discussed in the thesis:

- [ ] Multimedia support (images/diagrams) inside questions
- [ ] AI-assisted question suggestions and answer analysis
- [ ] Collaborative marketplace for sharing questions between instructors
- [ ] Exam simulation environment with timer
- [ ] Predictive analytics for question difficulty and student performance
- [ ] Adaptive testing based on prior performance
- [ ] Native mobile apps (iOS & Android) — with PWA as the interim solution

---

## 📄 Documentation

The full graduation thesis (system analysis, ERD, use-case diagram, interface walkthrough, and references) is included in this repository:

📘 **[Read the full thesis (PDF)](./docs/Exam-Generator-4th-Year-Thesis.pdf)**

---

## 👥 Authors

Fourth-Year project, Faculty of Informatics Engineering, **University of Aleppo** — 2025

- Ahmad Husam Shaaban
- Elias Noel Zikra
- Delir Youssef Alka
- Omar Mouhamad Sawas
- Gaïa Faraj-Allah Cheikho
- Maurice Abdullah Sayegh

**Supervisor:** PhD. Fadi Waleed Farha

---

## 📜 License

> No license is specified yet. Until a license is added, the work is "all rights reserved" by default.
> If you want others to be able to use or reference the code, consider adding an open-source license (e.g. [MIT](https://choosealicense.com/licenses/mit/)). Note that university projects may be subject to institutional rights — confirm before publishing the source publicly.

<div align="center">

⭐ If you find this project useful, consider giving it a star.

</div>
