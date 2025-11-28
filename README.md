# Studentų paraiškų valdymo sistema (PHP + SQLite)

Šis projektas yra pilnai funkcionuojanti studentų paraiškų valdymo sistema, sukurta naudojant **gryną PHP**, **SQLite**, aiškią **sluoksninę architektūrą** ir papildytas **saugumo mechanizmais** (PDO, CSRF, rate limiting, XSS prevencija, sesijų apsauga).

README parengtas taip, kad galėtum juo remtis pristatydamas projektą darbdaviui.

---

## 1. Funkcionalumas

### Studentas gali:

- Registruotis sistemoje
- Prisijungti su el. paštu ir slaptažodžiu
- Kurti naujas paraiškas (**ruošiniai**)
- Redaguoti ruošinius
- Pateikti paraiškas (**pateikta**)
- Negali pateikti daugiau kaip **3 vieno tipo pateiktų paraiškų**
- Matyti administratoriaus atmetimo komentarus (**atmestos paraiškos**)

### Administratorius gali:

- Prisijungti
- Peržiūrėti visas paraiškas
- Patvirtinti paraiškas (**patvirtinta**)
- Atmesti paraiškas su **privalomu komentaru**

---

## 2. Architektūra (Controller → Service → Repository + View)

Projektas suskirstytas į aiškius sluoksnius:

```text
public/ (entry points)
  → Controller (ApplicationController)
    → Service (ApplicationService)
      → Repository (ApplicationRepository)
        → DB (SQLite per PDO)

            ↓

          View (HTML šablonai views/)
```

### Controller sluoksnis

Failas: `src/ApplicationController.php`

- priima duomenis iš `public/applications/*.php`
- kviečia `ApplicationService`
- paruošia duomenis `View` šablonams

Pavyzdys: `ApplicationController::submit()` kviečia `ApplicationService::submitDraftForStudent()`.

### Service sluoksnis

Failas: `src/ApplicationService.php`

- įgyvendina verslo taisykles:
  - „max 3 submitted per tipo“
  - ar studentas gali redaguoti konkretų ruošinį
  - statusų keitimą (`draft` → `submitted` → `approved/rejected`)
- atskirtas nuo DB – dirba per `ApplicationRepository`.

### Repository sluoksnis

Failas: `src/ApplicationRepository.php`

- kapsuliuoja SQL užklausas:
  - `findById`
  - `findAllForStudent`
  - `findAll`
  - `countSubmittedByTypeForStudent`
  - `updateStatus`
  - `updateStatusAndComment`
  - `countRecentDraftsForStudent`
- naudojamas PDO `prepare/execute` (saugiau už `query` su kintamaisiais).

### View sluoksnis

Katalogas: `views/applications/`

- `list.php` – sąrašas + forma naujai paraiškai
- `edit.php` – ruošinio redagavimas
- `reject.php` – atmetimo forma

HTML atskirtas nuo logikos (Controller/Service neturi HTML).

---

## 3. SOLID principai

### SRP – Single Responsibility Principle

Kiekvienas sluoksnis turi vieną atsakomybę:

- Controller – request‘ų valdymas
- Service – verslo taisyklės
- Repository – duomenų prieiga
- View – atvaizdavimas (HTML)

### DIP – Dependency Inversion Principle

Priklausomybės tiekiamos per konstruktorių:

- `ApplicationController` gauna `ApplicationService`
- `ApplicationService` gauna `ApplicationRepository`
- `ApplicationRepository` gauna `PDO`

Tai leidžia lengvai keisti implementacijas (pvz., testuose naudoti in-memory SQLite).

Kiti SOLID principai (OCP, LSP, ISP) išplaukia iš tokio atsakomybių atskyrimo – sluoksniai nėra „perkrauti“ funkcionalumu.

---

## 4. Naudoti design pattern'ai

### Repository Pattern

**Kur:** `src/ApplicationRepository.php`  
**Kodėl:**

- visi SQL klausimai sukoncentruoti vienoje vietoje;
- Service/Controller nesirūpina DB detalėmis;
- galima pakeisti SQLite į MySQL/kitą DB, nekeičiat Controller/Service.

Pavyzdiniai metodai:

- `countSubmittedByTypeForStudent(int $studentId, string $type): int`
- `updateStatus(int $id, string $status): void`
- `updateStatusAndComment(int $id, string $status, string $comment): void`

### Service Layer Pattern

**Kur:** `src/ApplicationService.php`  
**Kodėl:**

- verslo logika atskirta nuo HTTP ir DB;
- lengviau testuoti (`ApplicationServiceTest.php`);
- Controller tampa „plonas“ – atsako tik už srautą.

Pavyzdiniai metodai:

- `createDraftForStudent(...)`
- `submitDraftForStudent(...)`
- `approveSubmittedByAdmin(int $id)`
- `rejectWithComment(int $id, string $comment)`
- `getApplicationsForUser(array $user)`

### MVC-like struktūra

**Kur:** `public/applications/*.php` + `ApplicationController` + `views/applications/*.php`

- public endpoint → controller → service → repository → view;
- HTML niekada nerašomas service/repository sluoksnyje.

---

## 5. Saugumas

### 5.1. Apsauga nuo SQL Injection

- Naudojamas **PDO** su `prepare()` / `execute()` visoms užklausoms.
- Nenaudojami string'ų sujungimai kaip `"... WHERE id=$id"` – vietoje to parametrai:
  - `:id`, `:student_id`, `:email` ir t. t.
- `db.php` nustatytas:
  - `PDO::ATTR_ERRMODE = PDO::ERRMODE_EXCEPTION`
  - `PDO::ATTR_DEFAULT_FETCH_MODE = PDO::FETCH_ASSOC`
  - `PDO::ATTR_EMULATE_PREPARES = false`

### 5.2. Apsauga nuo XSS (Cross-Site Scripting)

- Visos dinamiškai rodomos reikšmės šablonuose (`*.php` view) yra apgaubiamos:
  - `htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')`

Pvz.:

```php
<td><?php echo htmlspecialchars($app['title'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></td>
```

Taip naršyklei neleidžiama traktuoti įvedamo teksto kaip HTML/JS.

### 5.3. CSRF apsauga formoms

Failas: `src/csrf.php`

```php
function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_verify(?string $token): bool
{
    return isset($_SESSION['csrf_token']) && is_string($token) && hash_equals($_SESSION['csrf_token'], $token);
}
```

Kiekviena POST forma (login, register, create, edit, reject) turi:

```html
<input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>" />
```

O request apdorojime:

```php
$token = $_POST['csrf_token'] ?? null;
if (!csrf_verify($token)) {
    $error = 'Neteisingas saugumo žetonas. Perkraukite puslapį ir bandykite dar kartą.';
}
```

### 5.4. Login „rate limiting“

Login puslapyje (`public/login.php`):

- naudojamas `$_SESSION['login_attempts']` ir `$_SESSION['login_last_attempt']`;
- jei yra per daug nesėkmingų bandymų per trumpą laiką (pvz. 5 bandymai per 5 minutes), vartotojas gauna klaidą:
  - „Per daug nesėkmingų bandymų. Bandykite dar kartą po kelių minučių.“

Po sėkmingo prisijungimo:

```php
session_regenerate_id(true);
$_SESSION['user_id'] = $user['id'];
$_SESSION['login_attempts'] = 0;
```

### 5.5. Spamo / flood apsauga kuriant paraiškas

`ApplicationRepository`:

```php
public function countRecentDraftsForStudent(int $studentId, int $seconds): int
{
    $stmt = $this->pdo->prepare("
        SELECT COUNT(*)
        FROM applications
        WHERE student_id = :sid
          AND created_at >= :since
    ");
    $stmt->execute([
        ':sid' => $studentId,
        ':since' => date('Y-m-d H:i:s', time() - $seconds),
    ]);

    return (int)$stmt->fetchColumn();
}
```

`ApplicationService::createDraftForStudent(...)`:

```php
if ($this->repository->countRecentDraftsForStudent($studentId, 60) >= 5) {
    return 'Per daug bandymų sukurti paraiškas. Palaukite minutę ir bandykite vėl.';
}
```

### 5.6. Sesijų apsauga

- `session_regenerate_id(true)` po sėkmingo prisijungimo.
- Tikrinama, ar vartotojas egzistuoja DB, prieš laikant jį prisijungusiu.

---

## 6. Testai

Projektas turi **unit testus** su **PHPUnit**, kurie tikrina:

- verslo taisyklę: „max 3 submitted paraiškos vieno tipo studentui“
- statusų keitimą:
  - `submitted → approved`
  - `submitted → rejected` (su atmetimo komentaru)
- validaciją kuriant ruošinį

Failai:

- `tests/bootstrap.php`
- `tests/ApplicationServiceTest.php`
- `phpunit.xml`

Testai naudoja **in-memory SQLite** (`sqlite::memory:`), todėl nekeičia `data/app.sqlite`.

### Testų paleidimas

```bash
composer install
vendor/bin/phpunit
```

Tikėtinas rezultatas:

```text
OK (4 tests, 8 assertions)
```

---

## 7. Projekto struktūra

```text
students-app/
│
├── public/
│   ├── index.php
│   ├── login.php
│   ├── register.php
│   ├── logout.php
│   ├── css/
│   │   └── water.css
│   └── applications/
│       ├── index.php
│       ├── edit.php
│       └── reject.php
│
├── src/
│   ├── db.php
│   ├── View.php
│   ├── ApplicationRepository.php
│   ├── ApplicationService.php
│   ├── ApplicationController.php
│   ├── csrf.php
│   └── helpers.php (pvz. `e()` HTML escape funkcijai)
│
├── views/
│   └── applications/
│       ├── list.php
│       ├── edit.php
│       └── reject.php
│
├── tests/
│   ├── bootstrap.php
│   └── ApplicationServiceTest.php
│
├── data/
│   └── app.sqlite
│
├── composer.json
├── composer.lock
├── phpunit.xml
└── README.md
```

---

## 8. Paleidimo instrukcijos

1. Paleisti PHP serverį:

```bash
php -S localhost:8000 -t public
```

2. Atidaryti naršyklėje:

```text
http://localhost:8000/
```

3. Duomenų bazės failas sukuriamas automatiškai:

```text
data/app.sqlite
```

---

## 9. Ką daryčiau kitaip, jei turėčiau daugiau laiko?

- Įdėčiau **Composer autoloading pagal PSR-4** ir pašalinčiau `require` iš PHP failų.
- Sukurčiau atskirą **routerio sluoksnį** (`/index.php` → router → controller).
- Dar labiau išskaidyčiau statusų logiką naudojant **State pattern** (ApplicationStatus objektai).
- Parašyčiau daugiau testų:
  - integracinių testų Controller/View sluoksniams,
  - testų autentifikacijai (login/registration).
- Įdėčiau **Docker** (`Dockerfile + docker-compose.yml`) lengvam paleidimui įvairiose aplinkose.
- Pridėčiau REST API (`/api/applications`, `/api/login`) ir galimą SPA front-end (React/Vue).
- Sukurčiau gražesnį UI su Bootstrap/Tailwind (nors pagal užduotį dizainas nėra vertinimo kriterijus).

---

## 10. Autorius

Įrašyk:

- Vardas Pavardė
- GitHub profilis
- El. paštas
