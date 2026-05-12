# MindTrack — Personal Psychology & Self-Development Platform

**Symfony 6.4 | PHP | MySQL | PIDEV 3A24 — ESPRIT School of Engineering**

👥 **Team MindTrack:** [Barhoumi Amal] · [Soltani Ayoub] · [Ajmi Oussema] · [Dhbebnia Mohamed Aziz ] · [Elisabeth Djimadoumngar] 

---

## 🧠 Overview

**MindTrack** is a comprehensive web application built with **Symfony 6.4**, designed to support users on their **personal growth and psychological well-being journey** — without the need for a therapist or life coach. Through an intelligent, data-driven approach, MindTrack empowers individuals to set and track personal goals, practice guided mental health exercises, monitor their emotional states, and build healthy daily habits.

Developed by MindTrack team at **ESPRIT School of Engineering**, MindTrack bridges the gap between professional psychological support and everyday self-improvement tools.

---

## 🎯 Core Modules

MindTrack is structured around **5 main management modules**, each targeting a key pillar of personal development:

### 1. 👤 User Management

A secure and intelligent user system with advanced authentication and personalized psychological profiling.

**Features:**
- Full registration and login with role-based access (User / Admin)
- Password reset with strength validation and email verification
- Biometric login via **AI Face Recognition**
- Two-Factor Authentication (TOTP)
- Profile picture upload with encryption (**Defuse PHP**)
- Psychological profile per user (`profilpsychologique`): tracks **stress level** and **motivation level**
- Admin dashboard: ban/unban users, manage accounts, view statistics via **ChartJS**
- LLM-based content filtering for registration inputs
- HCaptcha bot protection

**Key Entities:** `utilisateur`, `profilpsychologique`, `password_reset_tokens`

---

### 2. 🎯 Personal Goal Management

Users define meaningful personal objectives and track them step by step through an intelligent planning system.

**Features:**
- Create, edit, and delete personal goals with start/end dates and statuses
- Break down each goal into **milestones** (`jalonprogression`) with target dates and completion percentages
- Generate **action plans** (`planaction`) with prioritized steps for each goal
- Use the **Smart Planner** (`planificateur`) to auto-organize daily workload based on capacity and mode
- Track overall **progression** with scores, personal notes, and time spent
- Visualize progress via charts and dashboards

**Key Entities:** `objectif`, `jalonprogression`, `planaction`, `planificateur`, `progression`

---

### 3. 🏋️ Exercise Management

A library of personalized mental and physical exercises, each with structured sessions and a to-do system for organized practice.

**Features:**
- Browse exercises by type (Méditation, Renforcement, etc.), difficulty (Débutant / Expert), and duration
- Detailed exercise cards with description and step-by-step approach (`demarche`)
- Schedule and log **exercise sessions** (`session`): real duration, results, and comments
- Manage a personal **to-do list** (`todo`) linked to exercises, with priority, color coding, and progress tracking
- Track badge progression based on completed exercises (`badge_progression`)
- Export exercise reports as PDF via **domPDF**

**Key Entities:** `exercice`, `session`, `todo`, `badge_progression`, `progression`

---

### 4. 😌 Mood & Emotions Management

A dedicated emotional intelligence module allowing users to log, analyze, and understand their emotional patterns over time.

**Features:**
- Daily mood logging with type (Happy, Sad, Anxious, etc.) and intensity level (1–10)
- Personal **emotional journal** (`journalemotionnel`) for free-form notes and reflections
- Visualize mood trends and emotional patterns over time via interactive charts
- AI-assisted insights based on mood history and psychological profile
- Connect emotional states to habits and goals for holistic well-being analysis

**Key Entities:** `humeur`, `journalemotionnel`

---

### 5. 🔄 Habit Management

A complete habit tracker enabling users to build positive routines and break negative ones through consistency and smart reminders.

**Features:**
- Create habits with name, frequency, objective, unit, and type: **BOOLEAN** (done/not done), **COUNT** (e.g., glasses of water), or **TIME** (e.g., minutes of reading)
- Define a **target value** per habit for measurable progress
- Daily **habit tracking** (`suivihabitude`): log completion status and actual values
- Smart **reminders** (`rappel_habitude`): configurable by days, time, and custom message — with auto-generation support
- View habit streaks, completion rates, and long-term consistency statistics

**Key Entities:** `habitude`, `suivihabitude`, `rappel_habitude`

---

## 🗄️ Database Schema Overview

| Table | Module | Description |
|---|---|---|
| `utilisateur` | User | Core user accounts with auth & profile data |
| `profilpsychologique` | User | Psychological profile: stress & motivation scores |
| `password_reset_tokens` | User | Secure password reset tokens |
| `objectif` | Goals | Personal goals with status and dates |
| `jalonprogression` | Goals | Milestones linked to goals |
| `planaction` | Goals | Prioritized action steps per goal |
| `planificateur` | Goals | AI-based daily planner linked to goals |
| `progression` | Goals / Exercises | Progress records with score, time, and notes |
| `exercice` | Exercises | Exercise library (type, difficulty, steps) |
| `session` | Exercises | Logged exercise sessions |
| `todo` | Exercises | Task list linked to exercises |
| `badge_progression` | Exercises | Gamification badges based on completed exercises |
| `humeur` | Mood | Daily mood entries with type and intensity |
| `journalemotionnel` | Mood | Personal emotional journal entries |
| `habitude` | Habits | Habit definitions (BOOLEAN / COUNT / TIME) |
| `suivihabitude` | Habits | Daily habit completion logs |
| `rappel_habitude` | Habits | Smart reminders for habits |

### Key Relationships

- `profilpsychologique` → `utilisateur` (each user has one psychological profile)
- `jalonprogression`, `planaction`, `planificateur` → `objectif` (all linked to a goal)
- `session`, `todo` → `exercice` (linked to a specific exercise)
- `suivihabitude`, `rappel_habitude` → `habitude` (tracking and reminders per habit)
- `password_reset_tokens` → `utilisateur` (secure reset flow per user)

---

## 🔐 Security & Authentication

- Password hashing and strength enforcement
- Biometric login via **AI Face Recognition** (face subject/image ID stored per user)
- TOTP Two-Factor Authentication (Google Authenticator compatible)
- Profile pictures encrypted with **Defuse PHP Encryption**
- Rate limiting and session guards
- LLM-based content moderation at registration
- **HCaptcha** bot detection on public forms
- Admin ban system (temporary or permanent)

---

## 📡 Tech Stack

### Frontend
- **Twig** templates + **Bootstrap 5**
- **JavaScript** / **jQuery** for interactivity
- **ChartJS** for progress and mood analytics dashboards
- **SweetAlert2** for elegant UX notifications
- Custom 404 error page

### Backend
- **Symfony 6.4** + **Doctrine ORM**
- **Defuse PHP** for image encryption
- **NLP & ML Models** for face recognition and content moderation
- **AJAX** for real-time features (search, habit tracking, mood logging)

### APIs & Integrations
- **SendGrid** — email notifications and password reset
- **OpenWeatherMap** — contextual weather data
- **domPDF** — generate custom PDF reports
- **HCaptcha** — AI-generated bot protection tests
- **FastAPI + HuggingFace** — ML microservice for content classification

### Database & Tools
- **MySQL** via XAMPP / MariaDB 10.4
- **PHPStorm** / **VS Code**
- **GitHub** for version control

---

## 🧠 Machine Learning: Content Moderation Microservice

A **FastAPI** microservice that uses HuggingFace Transformers to classify user-generated content and prevent toxic or harmful inputs.

### Features
- Detects toxic, offensive, or inappropriate language using `unitary/toxic-bert`
- Integrates in real-time with Symfony backend at registration and content submission

### Setup

```bash
pip install fastapi uvicorn transformers pydantic
```

#### Classify.py

```python
from fastapi import FastAPI
from pydantic import BaseModel
from transformers import pipeline

app = FastAPI()
classifier = pipeline("text-classification", model="unitary/toxic-bert")

class TextInput(BaseModel):
    text: str

@app.post("/classify")
async def classify(input: TextInput):
    results = classifier(input.text)
    return {"results": results}
```

#### Run

```bash
uvicorn Classify:app --reload --host 0.0.0.0 --port 8001
```

#### Test

```bash
curl -X POST "http://localhost:8001/classify" \
  -H "Content-Type: application/json" \
  -d '{"text": "Sample input text"}'
```

---

## 📁 Directory Structure

```
src/templates/
├── /user/                # Registration, login, profile, face auth
├── /objectif/            # Goal creation and management
├── /jalonprogression/    # Milestone tracking
├── /planaction/          # Action plan steps
├── /exercice/            # Exercise library
├── /session/             # Exercise session logs
├── /todo/                # Exercise to-do management
├── /humeur/              # Mood logging and charts
├── /journalemotionnel/   # Emotional journal
├── /habitude/            # Habit creation and management
├── /suivihabitude/       # Daily habit tracking
├── /rappel_habitude/     # Habit reminders
└── /admin/               # Admin dashboard & analytics
```

---

## 🚀 Getting Started

### Prerequisites

- PHP ≥ 8.1
- Composer
- Symfony CLI
- MySQL (via XAMPP)

### Installation

```bash
git clone https://github.com/your-repo/mindtrack.git
cd mindtrack
composer install
```

Edit `.env.local`:

```env
DATABASE_URL="mysql://root@127.0.0.1:3306/mindtrack2"
```

Then run:

```bash
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
php bin/console doctrine:fixtures:load  # Optional: load sample data
symfony server:start
```

---

## 🙏 Acknowledgments

Developed under the supervision of **Badiaa BOUHDID**, **Fadhel Essid**, **Omar Fitouri**, and **Soumaya SASSI** at **ESPRIT School of Engineering**.  
Special thanks to the open-source community behind Symfony, HuggingFace, Defuse PHP, ChartJS, SweetAlert2, and all integrated tools.
