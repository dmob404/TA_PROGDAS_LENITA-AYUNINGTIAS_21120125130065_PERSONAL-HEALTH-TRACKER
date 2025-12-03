
# Personal Health Tracker (PHP OOP)

Aplikasi Personal Health Tracker ini dibuat menggunakan PHP dengan konsep Object Oriented Programming (OOP).
User dapat melacak konsumsi air minum, jumlah langkah, mood harian, riwayat per hari, serta mengelola task queue produktif.
Aplikasi juga menyediakan fitur undo, weekly streak, dan theme toggle, tanpa JavaScript.

---

## Fitur Utama

* Tracking harian:

  * Air minum (ml)
  * Langkah
  * Mood harian
* Penyimpanan riwayat per hari (log.json)
* Badge/Status:

  * Hydration Master (air mencapai target)
  * Daily Walker (langkah mencapai target)
  * Perfect Day (air, langkah, dan mood lengkap)
  * Weekly Warrior (target tercapai 7 hari berturut-turut)
* Fitur Undo (menggunakan stack)
* Fitur Productive Task Queue (menggunakan queue FIFO)
* Tips dan motivasi harian berdasarkan kondisi user
* Theme toggle (mode neon dan soft)
* Multi-user:

  * Setiap user memiliki folder sendiri di `users/<username>/`
  * Data tidak tercampur antar user

---

## Teknologi dan Konsep yang Digunakan

* PHP (tanpa framework)
* HTML & CSS (GUI/tampilan)
* Session (`$_SESSION`) untuk:

  * Login user
  * State tracker harian
  * Penyimpanan theme aktif
  * Stack undo
* File JSON untuk penyimpanan data
* Konsep OOP:

  * Class dan constructor
  * Encapsulation
  * Abstraction
  * Polymorphism
* Struktur data:

  * Stack → fitur Undo
  * Queue → antrian tugas
* Penggunaan perulangan, pengkondisian, dan array
* Penyimpanan data per user

---

## Struktur Folder

```
project-root/
├─ index.php
├─ login.php
├─ logout.php
├─ style.css
├─ classes/
│  ├─ HabitClasses.php
│  └─ Tracker.php
└─ users/
   └─ <username>/
      ├─ log.json
      └─ queue.json
```

### Penjelasan File:

**index.php**
Halaman utama setelah login. Menampilkan form input, progress harian, badge, riwayat, task queue, dan memanggil method dari class Tracker dan Queue.

**login.php / logout.php**
Mengelola login sederhana menggunakan session.

**classes/HabitClasses.php**
Berisi:

* HabitBase (abstract class)
* DrinkHabit
* StepHabit
* MoodHabit
  Digunakan untuk memodelkan perilaku penambahan air, langkah, dan mood.

**classes/Tracker.php**
Class utama yang mengelola:

* Air, langkah, mood
* Log harian
* Stack untuk undo
* Weekly streak
* Penyimpanan data ke log.json

**users/<username>/**
Folder khusus user yang menyimpan:

* log.json → riwayat harian
* queue.json → daftar task user

---

## Cara Menjalankan

1. Aktifkan xampp Apache

2. Buka web dan ketik 

   ```
   http://localhost/TA_PROGDAS_LENITA%20AYUNINGTIAS_21120125130065_PERSONAL HEALTH TRACKER_PHP/login.php
   ```

3. Login, kemudian aplikasi akan mengarahkan ke halaman utama (index.php).

---

## Penjelasan Konsep OOP yang Digunakan

**1. Encapsulation**
Property pada class Tracker dan Habit dibuat private/protected.
Data hanya dapat diubah melalui method resmi seperti `add()`, `resetToday()`, dan `undo()`.

**2. Abstraction**
Class HabitBase adalah abstract class yang mendefinisikan struktur method `add()`, tanpa implementasi.
Turunan class wajib mengimplementasikannya.

**3. Polymorphism**
Class DrinkHabit, StepHabit, dan MoodHabit mengoverride method `add()` dengan perilaku berbeda:

* DrinkHabit / StepHabit → menambah nilai
* MoodHabit → mengganti nilai

**4. Constructor**
Class Tracker memiliki constructor yang:

* Menginisialisasi nilai dari session
* Membuat folder user jika belum ada
* Membaca dan menyiapkan file log.json

---

## Alur Kerja Utama

### A. Update Harian

1. User menginput air, langkah, dan mood.
2. index.php memanggil:

   ```
   $tracker->add($nilai_air, $nilai_steps, $nilai_mood);
   ```
3. Method add():

   * Menyimpan snapshot ke stack untuk undo
   * Mengupdate air dan langkah melalui objek Habit
   * Mengupdate mood jika diisi
   * Menyimpan hasil ke log.json
   * Menyinkronkan data ke session

### B. Undo

* Mengambil snapshot terakhir dari stack
* Mengembalikan air, langkah, dan mood ke kondisi sebelumnya
* Menyimpan ulang data ke log.json dan session

### C. Weekly Streak

* Mengecek 7 hari terakhir pada log.json
* Setiap hari harus memenuhi target air dan langkah
* Jika valid selama 7 hari, badge weekly streak aktif

### D. Task Queue

* Menggunakan class Queue dengan metode enqueue(), dequeue(), dan clear()
* Data queue disimpan ke queue.json per user
* Fitur Process Next mengambil tugas pertama (FIFO) dan menampilkan ringkasan status user


