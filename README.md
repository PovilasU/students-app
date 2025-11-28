# Studentų paraiškų valdymo sistema (PHP + SQLite)

Šis projektas yra pilnai funkcionuojanti studentų paraiškų valdymo sistema, sukurta naudojant **gryną PHP**, **SQLite** ir aiškią **sluoksninę architektūrą**, vadovaujantis **SOLID** principais bei naudojant **design pattern’us** (Repository, Service, MVC-like).

README struktūruotas taip, kad būtų lengva pristatyti projektą darbdaviui ir paaiškinti techninius sprendimus.

---

# 🔷 1. Projekto architektūra

Projektas sukurtas laikantis aiškaus sluoksnių suskirstymo:

```
Controller → Service → Repository → Database
                    ↓
                  View
```

## ✔ Controller sluoksnis

Failas: `src/ApplicationController.php`  
Atsakingas už:

- HTTP užklausų apdorojimą
- `Service` sluoksnio kvietimą
- duomenų perdavimą į `View`

Pvz.:  
`ApplicationController::submit()` – kviečia `ApplicationService::submitDraftForStudent()`.

---

## ✔ Service sluoksnis

Failas: `src/ApplicationService.php`  
Atsakingas už verslo logiką:

- taisyklė „**max 3 submitted per paraiškų tipą**“
- leidimų tikrinimas (studentas redaguoja tik savo draft)
- statusų keitimai (submitted → approved / rejected)
- validacijos

Pvz. metodai:

- `createDraftForStudent()`
- `submitDraftForStudent()`
- `approveSubmittedByAdmin()`
- `rejectWithComment()`

---

## ✔ Repository sluoksnis

Failas: `src/ApplicationRepository.php`  
Atsakingas už duomenų bazės operacijas:

- `countSubmittedByTypeForStudent()`
- `updateStatus()`
- `findDraftForStudent()`
- `rejectSubmittedWithComment()`

Tai leidžia pakeisti DB (pvz. MySQL) nekeičiant logikos.

---

## ✔ View sluoksnis

Katalogas: `views/applications/`  
Failai:

- `list.php`
- `edit.php`
- `reject.php`

Čia gyvena tik HTML – nėra jokios verslo logikos.

---

# 🔷 2. SOLID principų taikymas

Projektas praktiškai taiko 5 SOLID principus:

### ✔ SRP – Single Responsibility Principle

Kiekvienas sluoksnis turi tik vieną atsakomybę.  
Pvz.:

- Repository – tik DB
- Service – tik logika
- Controller – tik request valdymas
- View – tik HTML

### ✔ OCP – Open/Closed Principle

Repository galima praplėsti (pvz., MySQL), nekeičiat Service/Controller kodo.

### ✔ LSP – Liskov Substitution Principle

Controller gali dirbti su bet kuria Service/Repository implementacija.

### ✔ ISP – Interface Segregation Principle

Kiekviena klasė turi tik reikalingus metodus, nėra „didelio interfeiso“.

### ✔ DIP – Dependency Inversion Principle

Priklausomybės tiekiamos per konstruktorių:  
`ApplicationController → ApplicationService → ApplicationRepository → PDO`

---

# 🔷 3. Naudoti design pattern’ai

### ✔ Repository Pattern

Kodėl?

- SQL logika atskirta nuo verslo logikos
- Lengviau testuoti (mock'inti Repository)
- Lengva pakeisti DB variklį

Pvz.:  
`ApplicationRepository::countSubmittedByTypeForStudent()`  
`ApplicationRepository::updateStatus()`

### ✔ Service Layer Pattern

Kodėl?

- Verslo taisyklės atskirtos nuo controllerio
- Lengva testuoti Service logiką su unit testais
- Controller „plonas“, Service „protingas“

Pvz.:  
`ApplicationService::submitDraftForStudent()`  
tikrina:

- ar paraiška priklauso studentui
- ar ji draft
- ar neviršyta 3 submitted riba

### ✔ MVC-like pattern

Kodėl?

- logika izoliuota nuo HTML
- lengva keisti UI neliečiant logikos

---

# 🔷 4. Nuorodos į svarbiausias klases/metodus

| Kategorija | Failas                             | Metodai                                                                                                  |
| ---------- | ---------------------------------- | -------------------------------------------------------------------------------------------------------- |
| Controller | `src/ApplicationController.php`    | `list()`, `submit()`, `approve()`, `reject()`, `updateEdit()`                                            |
| Service    | `src/ApplicationService.php`       | `createDraftForStudent()`, `submitDraftForStudent()`, `approveSubmittedByAdmin()`, `rejectWithComment()` |
| Repository | `src/ApplicationRepository.php`    | `findById()`, `countSubmittedByTypeForStudent()`, `updateStatus()`                                       |
| DB         | `src/db.php`                       | `initDatabase()`, `initUsersTable()`, `initApplicationsTable()`                                          |
| Views      | `views/applications/`              | `list.php`, `edit.php`, `reject.php`                                                                     |
| Tests      | `tests/ApplicationServiceTest.php` | visi 4 testai                                                                                            |

---

# 🔷 5. Paleidimo instrukcijos

### ▶️ Serverio paleidimas

```
php -S localhost:8000 -t public
```

Atidaryk naršyklėje:

```
http://localhost:8000/
```

---

## 🔐 Prisijungimo duomenys (demo)

### Administratorius (seed)

- Email: **admin@example.com**
- Slaptažodis: **admin123**

### Studentas (seed)

- Email: **student@example.com**
- Slaptažodis: **student123**

### Nauji studentai:

```
/register.php
```

---

# 🔷 6. Unit testai

Šis projektas turi **4 unit testus**, kurie tikrina:

- taisyklę **„max 3 submitted per tipo“**
- statusų keitimą (`approved`, `rejected`)
- atmetimo komentaro išsaugojimą

### ▶️ Testų paleidimas

1. Įdiek priklausomybes:

```
composer install
```

2. Paleisk testus:

```
vendor/bin/phpunit
```

Tikėtinas output:

```
OK (4 tests, 8 assertions)
```

Testai naudoja `sqlite::memory:`, todėl:

- greiti
- izoliuoti
- nekeičia tikros DB

---

# 🔷 7. Ką padaryčiau kitaip, jei turėčiau daugiau laiko?

- Įdėčiau **Composer autoloading (PSR-4)** vietoj `require`.
- Sukurčiau **tikrą routerį** (vietoj atskirų PHP failų public kataloge).
- Išskaidyčiau „status“ į **State pattern** implementaciją.
- Sukurčiau API (REST) versiją.
- Pridėčiau Bootstrap/Tailwind šiuolaikiškam dizainui.
- Parašyčiau daugiau unit testų + testus Repository sluoksniui.
- Įdėčiau Docker (`docker-compose`) dėl lengvo paleidimo.

---

# 👤 Autorius

Įrašyk savo vardą, GitHub profilį ir el. paštą.
