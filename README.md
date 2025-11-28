# Studentų paraiškų valdymo sistema (PHP + SQLite)

Tai paprasta studentų paraiškų valdymo sistema, sukurta naudojant **gryną PHP** ir **SQLite** duomenų bazę.  
Sistemoje realizuoti du vartotojų vaidmenys:

- **Studentas** – gali kurti, redaguoti ir pateikti paraiškas (ruošinius)
- **Administratorius** – gali peržiūrėti pateiktas paraiškas, jas patvirtinti arba atmesti su komentaru

Projektas vystytas žingsnis po žingsnio, pagal pateiktą užduotį.

---

## 🚀 Funkcionalumas

### Studentas gali:

- Kurti naują paraišką (**draft**)
- Redaguoti paraiškos ruošinį (tik kol jis dar draft)
- Pateikti paraišką
- Pateikti ne daugiau kaip **3 vieno tipo paraiškas**
- Matyti administratoriaus **atmetimo komentarą**

### Administratorius gali:

- Matyti visas studentų paraiškas
- Patvirtinti pateiktas paraiškas
- Atmesti paraiškas privalomu komentaru

---

## 🗂 Projekto struktūra

```
students-app/
│
├── public/
│   ├── index.php
│   ├── login.php
│   ├── logout.php
│   │
│   ├── applications/
│   │   ├── index.php
│   │   ├── edit.php
│   │   └── reject.php
│
├── src/
│   ├── db.php
│   ├── ApplicationRepository.php
│   ├── ApplicationService.php
│   └── ApplicationController.php
│
├── data/
│   └── app.sqlite
│
└── README.md
```

---

## 🛠 Naudotos technologijos

- **PHP 8+**
- **SQLite**
- Be framework’ų (pure PHP)
- Architektūriniai sluoksniai:
  - Repository (DB užklausos)
  - Service (verslo logika)
  - Controller (veiksmų koordinavimas)

---

## 🔧 Projekto paleidimas lokaliai

### 1. Atsisiųsk / nuklonuok projektą

```
git clone https://github.com/PovilasU/students-app.git
```

### 2. Paleisk PHP serverį

```
php -S localhost:8000 -t public
```

### 3. Atidaryk naršyklėje

```
http://localhost:8000/
```

---

## 🔑 Prisijungimo naudotojai (demo)

| Vardas       | Rolė    |
| ------------ | ------- |
| Student User | student |
| Admin User   | admin   |

Slaptažodžio nereikia.

---

## ✔ Užduoties reikalavimai – įgyvendinimo santrauka

| Reikalavimas                               | Įgyvendinta | Pastabos              |
| ------------------------------------------ | ----------- | --------------------- |
| Studentas gali sukurti paraišką            | ✔           | Kuriama kaip „draft“  |
| Studentas gali redaguoti ruošinį           | ✔           |                       |
| Studentas gali pateikti ruošinį            | ✔           | Maks. 3 vieno tipo    |
| Administratorius mato visas paraiškas      | ✔           |                       |
| Administratorius gali patvirtinti          | ✔           |                       |
| Administratorius gali atmesti su komentaru | ✔           | Privalomas komentaras |
| Studentas mato atmetimo komentarą          | ✔           |                       |

---

## 💡 Galimi patobulinimai

- Tikras prisijungimas su slaptažodžiais
- Bootstrap/Tailwind UI
- PSR-4 autoloading
- Vieningas routeris
- PHPUnit testai

---

## 👤 Autorius

Įrašyk savo vardą, GitHub nuorodą ir el. paštą.
Povilas Urbonas, https://github.com/PovilasU
