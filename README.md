# README

## Asumsi

1. Perhitungan bulan baru tidak selalu dimulai dari hari Senin, sehingga 1 store bisa di visit 2x dalam seminggu jika ada dalam 2 bulan berbeda.  
   Contoh: 1 store di visit pada Senin 30 September dan Selasa 1 Oktober adalah valid (1 utk laporan Sep, 1 lagi utk laporan Okt).
2. Kunjungan biweekly store bisa dilakukan dalam 2 minggu berturut-turut, misal minggu 1 dan 2.

3. Perhitungan hari pertama dan akhir di setiap minggu mengikuti hari pertama di bulan tersebut.  
   Contoh: Oktober 2024 dimulai dari hari Selasa, sehingga Minggu pertama dimulai dari hari Selasa hingga Senin di pekan berikutnya. Memungkinkan terjadinya kunjungan pada hari Senin (sebagai hari akhir minggu pertama) dan keesokan harinya hari Selasa (sebagai kunjungan hari pertama minggu kedua).

4. Untuk mempermudah perhitungan di bulan Feb (hanya 28 hari), hanya menggunakan 4 minggu.

5. Mempertimbangkan jarak antar stores yang dikunjungi, namun tidak memperhitungkan jarak pergi dan pulang dari HQ.

## Background

-   Masalah ini termasuk dalam routing problem yang dimana mirip dengan Traveling Salesman Problem (TSP).
-   TSP yang simpel bisa diselesaikan dengan exact algorithms namun, penggunaan `Final Cycle` membuat saya mengkategorikan ini menjadi NP-hard routing problem.
-   3 feasible solusi yang muncul di pikiran saya:

1. **Greedy Algorithm**  
   Pendekatan heuristic yang biasa digunakan untuk solusi awal dari metode metaheuristic.

    **Pros**: implementasi yang cukup mudah dan simpel, eksekusi yang cepat

    **Cons**: mungkin menjadi solusi yang kurang akurat jika terdapat anomali data

2. **K-means Clustering Algorithm**  
   Biasanya digunakan untuk clustering tasks daripada routing problems, tapi di case ini, bisa digunakan untuk mengelompokan stores, dan membuat sales berfokus pada area yang lebih kecil (cluster)

    **Pros**: ekseusi yang cepat, mudah untuk pengembangan (mendefinisikan local search problem)

    **Cons**: susah untuk diimplementasikan di PHP (biasa menggunakan Python), limitasi 30 stores per hari membutuhkan adjustment dan customization.

3. **Metaheuristic**  
   Pendekatan yang banyak dilakukan untuk menyelesaikan routing problems yang kompleks.

    **Pros**: hasil yang akurat dan optimal

    **Cons**: untuk pembuatannya membutuhkan waktu lama (iterasi dan tuningnya), membutuhkan lebih detail masalah yang akan diselesaikan

Atas pertimbangan diatas, Saya memilih Greedy Algorithm untuk memastikan saya bisa mengimplementasikannya dalam kurun waktu 10 hari.

## Constraints

-   Sales = 10 -> max 30 stores a day
-   1 week = 6 working days = 1800 stores (10 x 30 x 6 = 1800)

#### Maximum stores/month for each cycle:

-   Weekly = 1800 stores
-   Biweekly = 3600 stores (1800 stores/week)
-   Monthly = 7200 stores (1800 stores/week)

#### Maximum stores allowed:

-   Weekly + Biweekly/2 + Monthly/4

## Implementation

1. Memberikan ranking untuk setiap toko diukur dari jaraknya ke toko sebelumnya. Memastikan bahwa setiap serial toko yang dikunjungi berdekatan. Untuk toko pertama akan diukur jaraknya dari lokasi HQ.

2. Memisahkan algoritma pemberian ranking dan penjadwalan untuk mengurangi kompleksitas computing pada pencarian ranking (O(n^2)). Sehingga pemberian ranking hanya perlu dilakukan pada saat ada perubahan stores data.

3. Algoritma penjadwalan dibuat hanya untuk 4 minggu dengan 6 hari kerja.

## Installation

1. Clone the repository:

    - ` git clone https://github.com/michaelpoernomo/michael-sse-test.git`

2. Install dependencies:

    - composer install

3. Copy the env file from env.example

4. Generate the application key:

    - php artisan key:generate

## Getting Started

Menjalankan algoritma ranking: http://localhost:8000/parse-data

Menjalankan algoritma scheduling: http://localhost:8000/

Tesing: `php artisan test`
