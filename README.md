# Studentų paraiškų valdymo sistema (PHP + SQLite)

Tai pilnai veikianti studentų paraiškų valdymo sistema, sukurta naudojant **gryną PHP**, **SQLite**, ir taikant gerąsias programavimo praktikas:

- **SOLID principai**
- **Repository pattern**
- **Service layer**
- **Controller layer**
- **View templates** (MVC-type)
- **Dependency Injection**
- **Separation of Concerns**

Sistema palaiko 2 naudotojų vaidmenis:

- **Studentas**
- **Administratorius**

---

## 🚀 Funkcionalumas

### 👨‍🎓 Studentas gali:
- Registruotis sistemoje (*nauja funkcija*)
- Prisijungti su el. paštu ir slaptažodžiu
- Kurti naują paraišką (**draft**)
- Redaguoti ruošinį
- Pateikti paraišką (**submitted**)
- Pateikti ne daugiau kaip **3 vieno tipo** paraiškų
- Matyti administratoriaus atmetimo komentarus (**rejected**)

### 🛡 Administratorius gali:
- Prisijungti (via seeded credentials)
- Matyti visas paraiškas
- Patvirtinti paraišką (**approved**)
- Atmesti paraišką su **privalomu komentaru**

---

## 🔐 Autentifikacija

### ✔ Tikras prisijungimas
- `email + password`
- `password_hash` saugojimui
- `password_verify()` tikrinimui

### ✔ Registracija (Studentams)
Kelias:  
`/register.php`

Registracijos metu:
- Tikrinamas el. paštas (unique)
- Tikrinamas slaptažodis (≥6 simboliai)
- Saugojamas `password_hash`
- Naujas vartotojas automatiškai prisijungiamas

Administratorius kūrimas vyksta automatiškai seed’inant DB.

---

## 🧠 Architektūra ir Dizaino Principai

### 🟦 SOLID  
Single Responsibility, Dependency Inversion ir kt.

### 🟩 Repository Pattern  
Visi DB užklausų veiksmai iškelti į `ApplicationRepository`.

### 🟧 Service Layer  
Verslo logika – max 3 aplikacijos / tipo, validacijos, permissions.

### 🟪 Controllers  
Tvarko request’us, kviečia service, perduoda duomenis į view.

### 🟦 Views (Templates)  
HTML iškelta į `/views/applications/`.

---

## 📁 Projekto struktūra

```
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

## 🧪 Prisijungimo duomenys (Demo)

### Studentas (seed):
- Email: **student@example.com**
- Slaptažodis: **student123**

### Administratorius:
- Email: **admin@example.com**
- Slaptažodis: **admin123**

### Nauji studentai:
- Registruojasi per `/register.php`

---

## 🛠 Paleidimas

1. Paleisti serverį:
   ```bash
   php -S localhost:8000 -t public
   ```
2. Naršyklėje atidaryti:
   ```text
   http://localhost:8000/
   ```

DB failas sukuriamas automatiškai:
```text
data/app.sqlite
```

---

## ✔ Užduoties reikalavimai – įgyvendinta

| Reikalavimas | Įgyvendinta |
|--------------|-------------|
| Studentas gali kurti paraišką | ✔ |
| Studentas gali redaguoti paraišką | ✔ |
| Studentas gali pateikti paraišką | ✔ |
| Max 3 submitted vieno tipo | ✔ |
| Admin mato visas paraiškas | ✔ |
| Admin gali patvirtinti paraišką | ✔ |
| Admin gali atmesti su komentaru | ✔ |
| Studentas mato komentarą | ✔ |
| Tikras login | ✔ |
| Registracija studentams | ✔ |
| MVC-like architektūra | ✔ |
| Service / Repository patterns | ✔ |
| SOLID principai | ✔ |

---

## 👤 Autorius

Įrašyk savo vardą, GitHub ir el. paštą.
