# Studentų Paraiškų Valdymo Sistema (PHP + SQLite)

Ši sistema yra pilnai funkcionuojama demonstracinė paraiškų valdymo aplikacija, sukurta naudojant **gryną PHP**, **SQLite**, **MVC-like struktūrą**, **Repository + Service** sluoksnius ir įdiegtas modernias **saugumo priemones** (CSRF, XSS apsauga, Login rate limiting, SQL injection prevencija).

README paruoštas taip, kad galėtum juo sėkmingai pristatyti projektą darbdaviui.

---

# 🧱 1. Funkcionalumas

## 👩‍🎓 Studentas gali:

- Registruotis sistemoje
- Prisijungti su el. paštu
- Kurti naujas paraiškas (ruošinius)
- Redaguoti ruošinius
- Pateikti paraiškas („Pateikta“)
- Negalima turėti daugiau nei **3 pateiktų paraiškų** to paties tipo
- Matyti atmetimo komentarus iš administratoriaus

## 🧑‍💼 Administratorius gali:

- Prisijungti
- Matyti visas paraiškas
- Patvirtinti paraišką („Patvirtinta“)
- Atmesti paraišką su privalomu komentaru

---

# 🏗️ 2. Architektūra

Projekto struktūra paremta aiškiais sluoksniais:

```
public/ (routing)
    → Controller
        → Service
            → Repository
                → SQLite DB

views/
    → HTML šablonai
```

Kiekvienas sluoksnis turi aiškią atsakomybę:

### ✔ Controller

- Apdoroja HTTP request'us
- Kviečia Service logiką
- Paduoda duomenis View šablonams

### ✔ Service (verslo logika)

- Taisyklė: max 3 submitted paraiškos per tipą
- Statusų keitimas: draft → submitted → approved / rejected
- Validacijos
- Rate limiting ruošinių spamui

### ✔ Repository (duomenų sluoksnis)

- SQL užklausos
- PDO prepared statements
- Grąžina duomenis Servisui

### ✔ View

- Tik HTML + PHP echo
- Duomenys atvaizduojami saugiai per `htmlspecialchars`

---

# 🧬 3. SOLID principai

### SRP (Single Responsibility Principle)

- Kiekviena klasė turi vieną atsakomybę:
  - Controller – request srautas
  - Service – taisyklės/verslo logika
  - Repository – SQL užklausos
  - View – tik HTML

### DIP (Dependency Inversion Principle)

- Service gauna Repository per konstruktorių
- Controller gauna Service per konstruktorių
- Leidžia lengvai testuoti ir keisti implementacijas

---

# 🧩 4. Naudoti Design Pattern'ai

### ✔ Repository Pattern

Failas: `src/ApplicationRepository.php`

Privalumai:

- SQL sukoncentruotas vienoje vietoje
- Service sluoksnis nežino, kaip veikia DB
- Lengva pakeisti DB (pvz. į MySQL)

### ✔ Service Layer Pattern

Failas: `src/ApplicationService.php`

Privalumai:

- Verslo taisyklės nepririštos prie UI ar DB
- Gali būti testuojama be naršyklės
- Controller išlieka „plonas“

### ✔ MVC-like View Rendering

Failai: `views/applications/*.php`

Privalumai:

- Aiškiai atskirtas HTML nuo logikos
- Lengva prižiūrėti UI

---

# 🔐 5. Saugumo priemonės

### 5.1 SQL Injection apsauga

- Visi SQL vykdomi su `prepare() + execute()`
- `PDO::ATTR_EMULATE_PREPARES = false`

### 5.2 XSS apsauga

- Visi HTML išvedami per:
  ```php
  htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
  ```

### 5.3 CSRF apsauga POST formoms

Failas: `src/csrf.php`

Formose:

```html
<input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>" />
```

Tikrinimas:

```php
if (!csrf_verify($_POST['csrf_token'] ?? null)) { ... }
```

### 5.4 Login Rate Limiting (anti-bruteforce)

- 5 nesėkmingi bandymai per 5 min → blokavimas
- Po sėkmingo login:
  ```php
  session_regenerate_id(true);
  ```

### 5.5 Paraiškų kūrimo anti-spam (Service sluoksnyje)

- Maks. 5 ruošiniai per 60 sekundžių

---

# 🧪 6. Testavimas (PHPUnit)

Sistema turi testus, kurie tikrina:

### ✔ verslo logiką:

- ruošinio kūrimą
- validaciją
- draft → submitted
- submitted → approved
- submitted → rejected (su komentaru)
- max 3 submitted per tipą
- rate limiting ruošinių kūrimui

Testai naudoja in-memory SQLite:

```
sqlite::memory:
```

### ▶ Testų paleidimas

```
composer install
vendor/bin/phpunit
```

Laukiamas rezultatas:

```
OK (8 tests, 20 assertions)
```

---

# 📁 7. Projekto struktūra

```
students-app/
│
├── public/
│   ├── login.php
│   ├── register.php
│   ├── logout.php
│   └── applications/
│       ├── index.php
│       ├── edit.php
│       └── reject.php
│
├── src/
│   ├── db.php
│   ├── View.php
│   ├── csrf.php
│   ├── ApplicationRepository.php
│   ├── ApplicationService.php
│   ├── ApplicationController.php
│
├── views/
│   └── applications/
│       ├── list.php
│       ├── edit.php
│       └── reject.php
│
├── tests/
│   ├── ApplicationServiceTest.php
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

# ▶️ 8. Paleidimas lokaliai

1. Įdiegti dependencies:

```
composer install
```

2. Paleisti serverį:

```
php -S localhost:8000 -t public
```

3. Atidaryti naršyklę:

```
http://localhost:8000/
```

Vartotojai automatiškai sukuriami:

| Rolė             | El. paštas          | Slaptažodis |
| ---------------- | ------------------- | ----------- |
| Studentas        | student@example.com | student123  |
| Administratorius | admin@example.com   | admin123    |

---

# 🚀 9. Ką būtų galima patobulinti ateityje

- Pilnas Router sluoksnis vietoje `public/*.php`
- PSR-4 Autoloading vietoje require
- State Pattern statusų valdymui
- REST API (`/api/...`)
- Docker konteinerizacija
- Daugiau integracinių testų
- UI pagerinimas (Bootstrap / Tailwind)

---

# 👤 10. Autorius

Įrašyk savo duomenis:

- **Vardas Pavardė**
- **El. paštas**
- **GitHub profilis**

---
