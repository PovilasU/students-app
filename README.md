# Studentų Paraiškų Valdymo Sistema (PHP + SQLite)

Ši sistema yra pilnai funkcionuojanti studentų paraiškų valdymo aplikacija, sukurta naudojant **gryną PHP**, **SQLite**, aiškią **Controller → Service → Repository → View** architektūrą, paprastą **REST API sluoksnį** (`/api/...`) ir demonstracinį **frontend'ą**, kuris vartoja šį API (`/api-demo/`). Taip pat įdiegtos pagrindinės **saugumo priemonės** (CSRF, XSS, login rate limiting, SQL injection prevencija).

---

## 📌 1. Funkcionalumas

### 👩‍🎓 Studentas gali:

- Registruotis sistemoje
- Prisijungti su el. paštu
- Kurti paraiškas (ruošinius)
- Redaguoti ruošinius
- Pateikti paraiškas („Pateikta“)
- Negalima pateikti daugiau nei **3 paraiškų vieno tipo**
- Matyti administratoriaus atmetimo komentarus

### 🧑‍💼 Administratorius gali:

- Prisijungti
- Matyti visų studentų paraiškas
- Patvirtinti paraiškas
- Atmesti paraiškas su privalomu komentaru

---

## 🏗️ 2. Architektūra (sluoksniai)

Projekto backend dalis suskaidyta į aiškius sluoksnius:

```text
public/ (entrypoint'ai ir API)
   ├─ applications/*.php   (HTML endpoint'ai)
   ├─ api/*.php            (REST API endpoint'ai)
   └─ index.php            (paprastas router'is /login, /register)

Controller (ApplicationController)
   ↓
Service (ApplicationService)
   ↓
Repository (ApplicationRepository)
   ↓
DB (SQLite per PDO, db.php)

View (views/*.php) – HTML šablonai
```

### Controller

Failas: `src/ApplicationController.php`

- priima duomenis iš `public/applications/*.php`,
- kviečia `ApplicationService`,
- paruošia duomenis `View` šablonams (`views/applications/*.php`).

### Service

Failas: `src/ApplicationService.php`

- verslo taisyklės:
  - „max 3 submitted per tipo“,
  - statusų keitimas `draft → submitted → approved / rejected`,
  - atmetimo komentaro validacija,
  - „draft“ redagavimo teisės,
  - paprastas rate limiting ruošinių kūrimui;
- nežino apie HTML ar HTTP – todėl lengvai testuojamas.

### Repository

Failas: `src/ApplicationRepository.php`

- kapsuliuoja SQL užklausas:
  - `findAllForStudent`, `findAll`, `findById`,
  - `countSubmittedByTypeForStudent`,
  - `insertDraft`, `updateDraft`,
  - `updateStatus`, `updateStatusAndComment`,
  - `countRecentDraftsForStudent`;
- naudoja `PDO::prepare` / `execute` (apsauga nuo SQL injection).

### View

Katalogai:

- `views/auth/*.php` – login / registracija,
- `views/applications/*.php` – paraiškų sąrašas, redagavimas, atmetimas.

Visas HTML atskirtas nuo verslo logikos – view tik atvaizduoja duomenis.

---

## 🧩 3. Naudoti design pattern'ai

### Repository Pattern

**Kur:** `src/ApplicationRepository.php`  
**Kodėl:**

- aiškus duomenų prieigos sluoksnis,
- galima pakeisti SQLite į kitą DB be pokyčių Controller/Service sluoksniuose,
- palengvina unit testų rašymą (naudojamas in-memory SQLite).

### Service Layer Pattern

**Kur:** `src/ApplicationService.php`

- visos taisyklės (max 3 paraiškos per tipą, statusų keitimas, komentaro validacija) vienoje vietoje;
- Controller neturi verslo logikos – perduoda duomenis į Service ir gauna rezultatą / klaidą.

### Paprastas Routing Pattern

**Kur:** `public/index.php` + `src/Router.php`

- `/login` ir `/register` nukreipiami per vieną router’į,
- paraiškų dalis (`/applications`) kol kas naudoja klasikinius entrypoint’us (`public/applications/*.php`).

---

## 🔐 4. Saugumo sprendimai

### 4.1. SQL Injection apsauga

- Visi užklausų parametrai paduodami per `PDO::prepare()` / `execute()`:
  - nenaudojama stringų konkatenacija `"... WHERE id=$id"`,
  - naudojami placeholder’iai `:id`, `:student_id`, `:email` ir t. t.
- `PDO::ATTR_EMULATE_PREPARES = false` – naudojami tik tikri prepared statements.

### 4.2. XSS apsauga

Visose view naudojama:

```php
htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
```

Taip vartotojo įvestas tekstas nėra vykdomas kaip HTML/JS naršyklėje.

### 4.3. CSRF apsauga

Failas: `src/csrf.php`

Kiekviena POST forma turi CSRF žetoną:

```html
<input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>" />
```

Serverio pusėje tikrinama:

```php
if (!csrf_verify($_POST['csrf_token'] ?? null)) {
    // klaida
}
```

### 4.4. Login rate limiting

- Po kelių nesėkmingų login bandymų (pvz. 5) – prisijungimas laikinai blokuojamas,
- sumažina bruteforce atakų riziką.

### 4.5. Sesijų apsauga

- `session_regenerate_id(true)` po sėkmingo prisijungimo,
- sumažina session fixation riziką.

### 4.6. Spam apsauga

- `ApplicationRepository::countRecentDraftsForStudent(...)` + `ApplicationService::createDraftForStudent(...)`:
  - neleidžia sukurti per daug ruošinių per trumpą laiką (pvz. >5 per 60 sekundžių).

---

## 🧪 5. Unit testai (PHPUnit)

### 5.1. Verslo logikos testai

Failas: `tests/ApplicationServiceTest.php`

Tikrina:

- ruošinio kūrimo validaciją (`createDraftForStudent`),
- „max 3 submitted“ taisyklę vienam tipui,
- ruošinio pateikimą (`draft → submitted`),
- draudimą pateikti kito studento paraišką,
- patvirtinimą (`submitted → approved` – tik iš `submitted`),
- atmetimą su komentaru (`submitted → rejected` + įrašomas komentaras),
- klaidą, kai atmetimo komentaras tuščias,
- ruošinių rate limit (per daug bandymų per minutę).

Naudojama in-memory SQLite (`sqlite::memory:`), todėl testai neapkrauna realios DB.

### 5.2. REST API testai

Papildomi testai, pvz.:

- `tests/ApiLoginTest.php`:
  - sėkminga autentifikacija per `/api/login`,
  - klaida su neteisingu slaptažodžiu;
- `tests/ApiApplicationsApiTest.php`:
  - `/api/applications` reikalauja prisijungimo,
  - studentas gali sukurti ruošinį ir jį mato sąraše,
  - administratorius mato visas paraiškas.

API testai naudoja tas pačias `ApiLogin` / `ApiApplications` funkcijas, kurios naudojamos `public/api/*.php` endpoint’uose.

### Testų paleidimas

```bash
composer install
vendor/bin/phpunit
```

Tikėtinas rezultatas, pvz.:

```text
OK (10+ tests, 30+ assertions)
```

---

## 🌐 6. Pilnas REST API (`/api/...`)

### 6.1. `/api/login` – prisijungimas (POST)

Failas: `public/api/login.php`  
Logika: `src/ApiLogin.php` (`api_login_handle()` funkcija).

**Request pavyzdys:**

```http
POST /api/login HTTP/1.1
Host: localhost:8000
Content-Type: application/json

{
  "email": "student@example.com",
  "password": "student123"
}
```

**Sėkmės atsakymas (200 OK):**

```json
{
  "success": true,
  "user": {
    "id": 2,
    "name": "Student User",
    "role": "student"
  }
}
```

Nesėkmės atveju – `401` ir JSON klaida.

Sesija (`PHPSESSID`) nustatoma taip pat, kaip ir HTML login – ją naudoja kiti API endpoint’ai.

---

### 6.2. `/api/applications` – sąrašas ir kūrimas

Failas: `public/api/applications.php`  
Logika: `src/ApiApplications.php` (`api_applications_handle()` funkcija).

#### GET /api/applications

- studentas mato tik savo paraiškas,
- administratorius mato visas paraiškas.

```http
GET /api/applications HTTP/1.1
Cookie: PHPSESSID=...
```

**Atsakymas (200 OK):**

```json
[
  {
    "id": 1,
    "student_id": 2,
    "title": "Test paraiška",
    "description": "Aprašymas",
    "type": "Stipendija",
    "status": "submitted",
    "rejection_comment": null,
    "created_at": "2025-11-28 12:00:00"
  }
]
```

#### GET /api/applications?id={id}

Gauti vieną paraišką (`id`):

- studentas gali gauti tik savo,
- administratorius – bet kurią.

```http
GET /api/applications?id=1
Cookie: PHPSESSID=...
```

---

#### POST /api/applications – sukurti ruošinį

Tik studentui.

```http
POST /api/applications HTTP/1.1
Content-Type: application/json
Cookie: PHPSESSID=...

{
  "title": "Nauja paraiška",
  "description": "Aprašymas",
  "type": "Stipendija"
}
```

**Sėkmė (201 Created):**

```json
{
  "success": true,
  "message": "Paraiškos ruošinys sukurtas sėkmingai."
}
```

---

#### PATCH /api/applications?id={id} – submit / approve / reject

Pagal `action` lauką JSON body.

**Submit (studentas):**

```http
PATCH /api/applications?id=1
Content-Type: application/json
Cookie: PHPSESSID=...

{
  "action": "submit"
}
```

**Approve (adminas):**

```http
PATCH /api/applications?id=1
Content-Type: application/json
Cookie: PHPSESSID=...

{
  "action": "approve"
}
```

**Reject (adminas):**

```http
PATCH /api/applications?id=1
Content-Type: application/json
Cookie: PHPSESSID=...

{
  "action": "reject",
  "comment": "Netinkami duomenys"
}
```

---

## 💻 7. Demo frontend'as, kuris naudoja REST API (`/api-demo/`)

Sukurtas lengvas demo frontend'as (vienas HTML failas su JS), kuris nėra susijęs su pagrindine HTML sąsaja ir naudojamas tik API demonstravimui.

### Failas: `public/api-demo/index.html`

- Prisijungimo forma (email + password),
- statuso blokas (prisijungęs vartotojas, rolė),
- paraiškų sąrašo lentelė (naudojant `/api/applications`),
- forma naujam ruošiniui sukurti (POST `/api/applications`),
- mygtukai:
  - studentui: „Pateikti“ (`action: "submit"`),
  - adminui: „Patvirtinti“ (`action: "approve"`), „Atmesti“ (`action: "reject"` + `prompt` komentarui).

Demo frontendas bendrauja su backend'u per `fetch` ir JSON, naudoja tuos pačius API endpoint'us, kas pademonstruoja, kad **verslo logika yra nepririšta prie UI**.

---

## 🧪 8. Kaip pačiam testuoti REST API ir demo frontend'ą

### 8.1. Paleisti serverį

```bash
php -S localhost:8000 -t public
```

### 8.2. Testavimas per `curl` (CLI)

1. Prisijungti studentu:

```bash
curl -i -c cookies.txt   -H "Content-Type: application/json"   -d '{"email":"student@example.com","password":"student123"}'   http://localhost:8000/api/login
```

2. Sukurti ruošinį:

```bash
curl -i -b cookies.txt   -H "Content-Type: application/json"   -d '{"title":"API Paraiška","description":"Aprašymas","type":"Stipendija"}'   http://localhost:8000/api/applications
```

3. Gauti sąrašą:

```bash
curl -i -b cookies.txt http://localhost:8000/api/applications
```

### 8.3. Testavimas per Postman / Thunder Client

- POST `/api/login` – login (JSON body su email/password),
- GET `/api/applications` – gauti sąrašą,
- POST `/api/applications` – sukurti ruošinį,
- PATCH `/api/applications?id={id}` – submit/approve/reject su JSON body (`action` + `comment`).

### 8.4. Testavimas per demo frontend'ą

1. Atidaryk naršyklėje:

```text
http://localhost:8000/api-demo/index.html
```

2. Prisijunk su:

- studentas: `student@example.com` / `student123`,
- adminas: `admin@example.com` / `admin123`.

3. Išbandyk:

- studentu – kurk paraiškas, pateik jas,
- adminu – patvirtink / atmesk per UI mygtukus.

---

## 📁 9. Projekto struktūra (su API ir demo frontend'u)

```text
students-app/
│
├── public/
│   ├── index.php
│   ├── login.php          # legacy
│   ├── register.php       # legacy
│   ├── logout.php
│   ├── api/
│   │   ├── login.php
│   │   └── applications.php
│   ├── api-demo/
│   │   └── index.html     # demo SPA, naudojanti REST API
│   ├── applications/
│   │   ├── index.php
│   │   ├── edit.php
│   │   └── reject.php
│   └── css/
│       └── water.css
│
├── src/
│   ├── db.php
│   ├── View.php
│   ├── csrf.php
│   ├── Router.php
│   ├── ApplicationRepository.php
│   ├── ApplicationService.php
│   ├── ApplicationController.php
│   ├── ApiLogin.php
│   └── ApiApplications.php
│
├── views/
│   ├── auth/
│   │   ├── login.php
│   │   └── register.php
│   └── applications/
│       ├── list.php
│       ├── edit.php
│       └── reject.php
│
├── tests/
│   ├── ApplicationServiceTest.php
│   ├── ApiLoginTest.php
│   ├── ApiApplicationsApiTest.php
│   └── bootstrap.php
│
├── data/
│   └── app.sqlite
│
├── composer.json
├── phpunit.xml
└── README.md
```

---

## 🧠 10. Ką daryčiau kitaip, jei turėčiau daugiau laiko

Trumpai:

- Pilnai perkelčiau visus endpoint'us ant vieno Router/Front Controller sprendimo (`index.php` + router rules), vietoje `public/*.php` entrypoint’ų.
- Naudočiau PSR-4 autoloading per Composer vietoje `require` rankinių įtraukimų.
- Įdiegčiau State pattern paraiškų būsenoms (`draft/submitted/approved/rejected` kaip atskiri state objektai).
- Išplėsčiau REST API (pilnas CRUD, filtravimas, pagination, atskira `/api/users/...` dalis).
- Pakeisčiau demo frontend'ą į pilnavertį SPA (React/Vue) su TypeScript ir geresniu UI (Tailwind / Bootstrap).
- Pridėčiau Docker aplinką (vienas `docker-compose up` vietoj manual setup).
- Pridėčiau integracinius testus per HTTP (pvz. pest/phpunit + symfony/http-client), kad būtų padengtas visas kelias `request → response`.

---

## 👤 11. Autorius

Įrašykite savo duomenis:

- **Povilas Urbonas**
- **El. paštas**
- **GitHub profilis https://github.com/PovilasU**
