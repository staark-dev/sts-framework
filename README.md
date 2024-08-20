# **Staark One Framework**

**Staark One** este un framework PHP personalizat, conceput pentru a oferi o soluție completă și modulară pentru dezvoltarea aplicațiilor web moderne. Proiectul combină diverse sisteme și tehnologii pentru a permite dezvoltatorilor să construiască aplicații robuste, flexibile și scalabile. Acest framework este ideal pentru cei care doresc să aibă un control total asupra arhitecturii și a funcționalităților unei aplicații.

## **Caracteristici principale**

### 1. **Sistem dinamic de rute cu suport pentru grupuri și middleware**
Staark One oferă un sistem de rutare flexibil, care permite definirea rutelor prin metode statice și suportă grupuri de rute și middleware-uri. Sistemul permite de asemenea crearea de rute RESTful, suportând toate metodele HTTP standard (`GET`, `POST`, `PUT`, `DELETE`).

### 2. **Sistem de teme personalizabile**
Framework-ul include un `ThemeManager` care gestionează temele aplicației. Suportă încărcarea dinamică a temelor, gestionarea resurselor de temă (CSS, JS, imagini) și oferă funcționalități avansate de templating, cum ar fi `@extends`, `@include`, `@yield`, și multe altele.

### 3. **Templating Engine cu suport pentru directive personalizate**
Staark One include un motor de template ce permite utilizarea de variabile, secțiuni, layout-uri și directive personalizate direct în fișierele de template. Suportă variabile, traduceri, sesiuni, generarea URL-urilor, și multe altele.

### 4. **Sistem de module și pluginuri extensibil**
Framework-ul este modular, permițând adăugarea de module și pluginuri personalizate care pot extinde funcționalitatea de bază. Fiecare modul poate avea propriile rute, middleware-uri, teme, și configurări.

### 5. **Sistem de traducere pentru aplicații multilingve**
Staark One include un sistem de traduceri care permite gestionarea aplicațiilor în mai multe limbi. Utilizatorii pot defini fișiere de limbă și utiliza funcția `trans()` pentru a prelua traducerile necesare în template-uri și controlere.

### 6. **Sistem de sesiuni personalizat**
Framework-ul include un `CustomSessionHandler` care permite gestionarea sesiunilor într-o bază de date, oferind o securitate sporită și flexibilitate în gestionarea datelor de sesiune.

### 7. **ORM simplificat cu QueryBuilder modular**
Staark One oferă un ORM simplificat care permite interacțiunea cu baza de date prin intermediul unui QueryBuilder modular. Acest lucru oferă dezvoltatorilor un control detaliat asupra interogărilor SQL generate.

### 8. **Sistem de evenimente și hook-uri**
Framework-ul permite definirea de evenimente și hook-uri personalizate care pot fi utilizate pentru a extinde funcționalitatea aplicației într-un mod clar și organizat.

### 9. **Cache Management**
Sistemul de cache inclus permite stocarea temporară a conținutului generat, pentru a îmbunătăți performanța aplicației. Template-urile și alte date pot fi salvate în cache și servite rapid la cerere.

### 10. **Sistem de permisiuni și autentificare**
Staark One include un sistem de permisiuni și autentificare care permite gestionarea accesului utilizatorilor la diferite secțiuni ale aplicației. Middleware-ul de autentificare și clasele de permisiuni pot fi personalizate după nevoile aplicației.

## **Structură și Organizare**

Staark One este organizat pentru a facilita dezvoltarea rapidă și întreținerea ușoară a aplicațiilor complexe. Fiecare componentă a framework-ului este modulară și poate fi extinsă sau înlocuită după necesități.

- **Core**: Conține componentele de bază ale framework-ului, inclusiv routerul, gestionarul de teme, motorul de template, și managerul de sesiuni.
- **App**: Aici sunt localizate controlerele, modelele și fișierele specifice aplicației.
- **Config**: Toate configurările aplicației sunt stocate aici, inclusiv cele pentru baza de date, sesiuni, teme și cache.
- **Routes**: Fișierele de rute sunt stocate aici, organizând rutele aplicației într-un mod clar și intuitiv.
- **Resources**: Conține resursele aplicației, cum ar fi fișierele de limbă, temele, și alte active statice.

## **Cum să începi**

1. **Instalare**: Clonează acest repository și rulează `composer install` pentru a instala toate dependențele necesare.
2. **Configurare**: Setează configurațiile în fișierele din directorul `config`, cum ar fi setările pentru baza de date, tema activă, și altele.
3. **Definirea Rutelor**: Adaugă rutele tale în fișierele din directorul `routes`.
4. **Creare de Module**: Extinde funcționalitatea framework-ului prin crearea de module și pluginuri personalizate.
5. **Gestionarea Temei**: Personalizează tema activă sau creează teme noi în directorul `resources/themes`.

## **Contribuție**

Orice contribuție la proiect este binevenită! Poți contribui prin:
- Crearea de pull request-uri pentru adăugarea de funcționalități noi.
- Raportarea de bug-uri sau probleme în secțiunea de Issues.
- Îmbunătățirea documentației sau adăugarea de exemple de cod.

## **Licență**

Acest proiect este licențiat sub Licența MIT. Vezi fișierul `LICENSE` pentru mai multe detalii.
