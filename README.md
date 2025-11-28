# Studentų paraiškų valdymo sistema (PHP + SQLite)

Tai pilnai veikianti studentų paraiškų valdymo sistema, sukurta naudojant **gryną PHP**, **SQLite** bei taikant modernius programinės įrangos architektūros principus:  
**SOLID**, **Repository pattern**, **Service layer**, **Controller layer**, **View templates**, **Separation of Concerns** ir **MVC tipo struktūra**.

Sistema palaiko du naudotojų vaidmenis:

- **Studentas** – gali kurti, redaguoti ir pateikti paraiškas (draft → submitted)
- **Administratorius** – gali peržiūrėti, patvirtinti arba atmesti paraiškas su komentaru

Projektas sukurtas siekiant pademonstruoti gerą PHP kodo struktūrą be framework’o.

---

## 🚀 Funkcionalumas

### Studentas
- Kuria naują paraišką (**draft**)
- Redaguoja esamą ruošinį
- Pateikia paraišką (**submitted**)
- Negali pateikti daugiau nei **3 vieno tipo** paraiškų
- Matyti administratoriaus **atmetimo komentarą**

### Administratorius
- Matyti visas studentų paraiškas
- Patvirtinti paraiškas (**approved**)
- Atmesti paraiškas (**rejected**) su **privalomu komentaru**

---

# 🧠 Architektūra ir dizaino principai

Projektas sukurtas naudojant kelis svarbius programavimo principus:

---

## 🟦 SOLID principai

### ✔ S – Single Responsibility Principle  
Kiekvienas komponentas turi vieną atsakomybę:  
Repository → DB logika  
Service → verslo logika  
Controller → request'ai  
View → HTML šablonai  

### ✔ O – Open/Closed Principle  
Sistemos komponentai lengvai plečiami nekeičiant bazinio kodo.

### ✔ L – Liskov Substitution Principle  
Kodas lengvai pakeičiamas alternatyviomis implementacijomis.

### ✔ I – Interface Segregation Principle  
Funkcionalumas suskaidytas į mažus, tikslius komponentus.

### ✔ D – Dependency Inversion Principle  
Controller gauna Service ir Repository per dependency injection.

---

## 🟩 Repository pattern

`ApplicationRepository.php` atsakingas tik už duomenų bazės užklausas.  
Galima lengvai pakeisti SQLite į MySQL ar PostgreSQL nekeičiat controllerių.

---

## 🟧 Service layer

`ApplicationService.php` įgyvendina visą verslo logiką:

- max 3 submitted per type
- validacijos
- leidimų tikrinimas

---

## 🟪 Controller layer

`ApplicationController.php` atsakingas už:

- veiksmų valdymą (submit/edit/reject)
- response duomenų paruošimą
- klaidų valdymą

---

## 🟦 View templates (MVC)

Visas HTML iškeltas į `/views/applications/`, o controller tik perduoda duomenis į šablonus.

Tai suteikia:

- švarų kodą
- lengvesnę plėtrą
- geresnį skaidrumą

---

## 🗂 Projekto struktūra

```
students-app/
│
├── public/
│   ├── index.php
│   ├── login.php
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
│   └── ApplicationController.php
│
├── views/
│   └── applications/
│       ├── list.php
│       ├── edit.php
│       └── reject.php
│
├── data/
│   └── app.sqlite
│
└── README.md
```

---

## 🔧 Paleidimas

1. ```
   php -S localhost:8000 -t public
   ```
2. Naršyklėje atidaryti:  
   `http://localhost:8000/`

---

## 🔐 Prisijungimo duomenys

### Studentas
- Email: **student@example.com**
- Slaptažodis: **student123**

### Administratorius
- Email: **admin@example.com**
- Slaptažodis: **admin123**

---

## ✔ Užduoties reikalavimai – įgyvendinti

| Reikalavimas | Įgyvendinta |
|--------------|-------------|
| Studentas gali kurti paraišką | ✔ |
| Studentas gali redaguoti ruošinį | ✔ |
| Studentas gali pateikti | ✔ |
| Max 3 per tipą | ✔ |
| Admin mato visas paraiškas | ✔ |
| Admin gali patvirtinti | ✔ |
| Admin gali atmesti su komentaru | ✔ |
| Studentas mato komentarą | ✔ |
| Tikras login | ✔ |
| MVC-like architektūra | ✔ |

---

## 👤 Autorius

Įrašyk savo vardą, GitHub ir el. paštą.
