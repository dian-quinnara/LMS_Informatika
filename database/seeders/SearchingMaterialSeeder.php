<?php

namespace Database\Seeders;

use App\Models\Material;
use App\Models\Subject;
use Illuminate\Database\Seeder;
use JsonException;

class SearchingMaterialSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $searching = Subject::query()->firstOrCreate(
            ['code' => 'IF-SEARCHING'],
            [
                'name' => 'Searching',
                'description' => 'Materi algoritma pencarian data.',
            ],
        );

        foreach ($this->lessons() as $lesson) {
            Material::query()->updateOrCreate(
                ['slug' => $lesson['slug']],
                [
                    'subject_id' => $searching->id,
                    'title' => $lesson['title'],
                    'summary' => $lesson['description'],
                    'content' => json_encode($lesson['sections'], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
                    'sort_order' => $lesson['sort_order'],
                ],
            );
        }
    }

    /**
     * @return array<int, array{slug: string, title: string, description: string, sort_order: int, sections: array<int, array<string, mixed>>}>
     *
     * @throws JsonException
     */
    private function lessons(): array
    {
        return [
            [
                'slug' => 'sequential-search',
                'title' => 'Sequential Search',
                'description' => 'Memahami cara mencari data satu per satu secara berurutan.',
                'sort_order' => 1,
                'sections' => [
                    [
                        'title' => 'Tujuan Pembelajaran',
                        'type' => 'list',
                        'intro' => 'Setelah mempelajari materi ini, siswa diharapkan mampu:',
                        'items' => [
                            'Menjelaskan pengertian Sequential Search.',
                            'Menjelaskan dan melakukan langkah pencarian secara berurutan.',
                            'Menulis pseudocode dan membaca implementasi Python sederhana.',
                            'Menganalisis kelebihan dan keterbatasan Sequential Search.',
                        ],
                    ],
                    [
                        'title' => 'Pemantik',
                        'type' => 'paragraphs',
                        'paragraphs' => [
                            'Bayangkan kamu mencari nama teman di daftar siswa yang belum diurutkan berdasarkan nama. Kamu mulai dari nama pertama, memeriksa satu per satu, lalu berhenti saat nama temanmu ditemukan. Itulah ide dasar Sequential Search.',
                        ],
                    ],
                    [
                        'title' => 'Pengertian Sequential Search',
                        'type' => 'paragraphs',
                        'paragraphs' => [
                            'Sequential Search, atau pencarian berurutan, adalah algoritma untuk menemukan nilai dengan membandingkan target dengan setiap elemen dari awal hingga target ditemukan atau semua elemen sudah diperiksa.',
                            'Tujuannya adalah mengetahui apakah data tertentu ada di dalam kumpulan data dan, jika ada, menemukan posisinya. Algoritma ini cocok untuk daftar kecil atau data yang belum diurutkan. Data tidak perlu disusun terlebih dahulu.',
                            'Pencarian berjalan dari kiri ke kanan (atau dari elemen pertama ke terakhir). Begitu nilai cocok, pencarian dapat dihentikan. Jika seluruh elemen tidak cocok, hasilnya adalah data tidak ditemukan.',
                        ],
                    ],
                    [
                        'title' => 'Cara Kerja Sequential Search',
                        'type' => 'steps',
                        'intro' => 'Cari target 18 pada data [12, 7, 25, 18, 30, 10]. Periksa elemen dari posisi pertama:',
                        'items' => [
                            ['title' => 'Posisi 1: 12', 'detail' => 'Bandingkan 12 dengan 18. Nilainya tidak sama, lanjut ke elemen berikutnya.'],
                            ['title' => 'Posisi 2: 7', 'detail' => 'Bandingkan 7 dengan 18. Nilainya tidak sama, lanjut.'],
                            ['title' => 'Posisi 3: 25', 'detail' => 'Bandingkan 25 dengan 18. Nilainya tidak sama, lanjut.'],
                            ['title' => 'Posisi 4: 18', 'detail' => 'Bandingkan 18 dengan 18. Nilainya sama, target ditemukan. Pencarian berhenti.'],
                        ],
                        'outro' => 'Hasil: target 18 ditemukan pada posisi ke-4 (indeks 3 jika indeks dimulai dari 0).',
                    ],
                    [
                        'title' => 'Ilustrasi',
                        'type' => 'array',
                        'intro' => 'Setiap elemen diperiksa berurutan. Elemen yang cocok dengan target ditandai.',
                        'values' => ['12', '7', '25', '18', '30', '10'],
                        'target' => '18',
                        'match_index' => 3,
                        'outro' => 'Karena 18 ditemukan pada elemen keempat, elemen setelahnya tidak perlu diperiksa.',
                    ],
                    [
                        'title' => 'Pseudocode',
                        'type' => 'code',
                        'language' => 'Pseudocode',
                        'code' => "PROCEDURE SequentialSearch(data, target)\n    FOR index FROM 0 TO LENGTH(data) - 1\n        IF data[index] = target THEN\n            RETURN index\n    END FOR\n    RETURN -1  // target tidak ditemukan\nEND PROCEDURE",
                    ],
                    [
                        'title' => 'Contoh Python',
                        'type' => 'code',
                        'language' => 'Python',
                        'code' => "def sequential_search(data, target):\n    # Periksa setiap nilai dari awal daftar\n    for index, value in enumerate(data):\n        # Jika nilainya sama dengan target, kembalikan indeksnya\n        if value == target:\n            return index\n\n    # Nilai -1 menandakan target tidak ditemukan\n    return -1\n\ndata = [12, 7, 25, 18, 30, 10]\ntarget = 18\nposition = sequential_search(data, target)\n\nif position != -1:\n    print(f\"Target ditemukan pada indeks {position}\")\nelse:\n    print(\"Target tidak ditemukan\")",
                    ],
                    [
                        'title' => 'Contoh Kasus',
                        'type' => 'paragraphs',
                        'paragraphs' => [
                            'Petugas perpustakaan memiliki daftar kode buku yang baru dikembalikan dan belum diurutkan. Untuk memeriksa apakah kode buku tertentu ada di daftar, petugas dapat membandingkannya dengan kode pertama, kedua, dan seterusnya sampai cocok atau daftar habis.',
                        ],
                    ],
                    [
                        'title' => 'Kelebihan',
                        'type' => 'list',
                        'items' => [
                            'Mudah dipahami dan ditulis, bahkan untuk pemula.',
                            'Dapat digunakan pada data yang belum terurut.',
                            'Tidak memerlukan struktur data atau persiapan khusus.',
                        ],
                    ],
                    [
                        'title' => 'Kekurangan',
                        'type' => 'list',
                        'items' => [
                            'Pada daftar panjang, banyak elemen mungkin harus diperiksa.',
                            'Pencarian berulang pada data yang sama dapat menjadi lambat.',
                        ],
                    ],
                    [
                        'title' => 'Kompleksitas',
                        'type' => 'paragraphs',
                        'paragraphs' => [
                            'Jika target ada di elemen pertama, cukup satu pemeriksaan. Namun, pada kondisi terburuk target berada di akhir atau tidak ada, sehingga sebanyak n elemen perlu diperiksa untuk daftar dengan n elemen. Karena jumlah pemeriksaan bertambah seiring jumlah data, kompleksitas waktu terburuknya adalah O(n).',
                            'Algoritma ini hanya memakai beberapa variabel tambahan, sehingga kebutuhan ruang tambahannya O(1).',
                        ],
                    ],
                    [
                        'title' => 'Rangkuman',
                        'type' => 'list',
                        'items' => [
                            'Sequential Search memeriksa elemen satu per satu dari awal.',
                            'Data tidak harus diurutkan.',
                            'Pencarian berhenti saat target ditemukan atau semua data selesai diperiksa.',
                            'Kompleksitas waktu terburuk adalah O(n).',
                        ],
                    ],
                    [
                        'title' => 'Pertanyaan Refleksi',
                        'type' => 'list',
                        'items' => [
                            'Mengapa Sequential Search tetap dapat digunakan saat data belum terurut?',
                            'Berapa kali perbandingan diperlukan untuk menemukan 30 pada contoh data tadi?',
                            'Apa yang menjadi hasil pencarian jika target tidak ada di dalam daftar?',
                        ],
                    ],
                ],
            ],
            [
                'slug' => 'binary-search',
                'title' => 'Binary Search',
                'description' => 'Memahami cara mencari data terurut dengan membagi area pencarian menjadi dua.',
                'sort_order' => 2,
                'sections' => [
                    [
                        'title' => 'Tujuan Pembelajaran',
                        'type' => 'list',
                        'intro' => 'Setelah mempelajari materi ini, siswa diharapkan mampu:',
                        'items' => [
                            'Menjelaskan pengertian dan syarat Binary Search.',
                            'Menentukan nilai low, high, dan mid pada setiap langkah.',
                            'Menulis pseudocode dan membaca implementasi Python sederhana.',
                            'Menganalisis kelebihan, keterbatasan, dan kompleksitas Binary Search.',
                        ],
                    ],
                    [
                        'title' => 'Pemantik',
                        'type' => 'paragraphs',
                        'paragraphs' => [
                            'Saat mencari kata di kamus, kamu biasanya tidak membaca halaman dari awal satu per satu. Kamu membuka bagian tengah, melihat apakah kata yang dicari berada sebelum atau sesudahnya, lalu mempersempit bagian yang diperiksa. Cara membagi area seperti ini menjadi ide Binary Search.',
                        ],
                    ],
                    [
                        'title' => 'Pengertian Binary Search',
                        'type' => 'paragraphs',
                        'paragraphs' => [
                            'Binary Search adalah algoritma pencarian yang membandingkan target dengan elemen tengah dari area pencarian. Berdasarkan hasil perbandingan, algoritma membuang separuh area yang tidak mungkin berisi target, lalu mengulangi langkah tersebut.',
                            'Pada setiap langkah, low menunjukkan indeks batas bawah, high menunjukkan indeks batas atas, dan mid menunjukkan indeks tengah. Jika nilai di mid sama dengan target, pencarian berhasil. Jika belum sama, area pencarian dipersempit.',
                        ],
                    ],
                    [
                        'title' => 'Syarat Binary Search',
                        'type' => 'list',
                        'intro' => 'Data harus sudah terurut sebelum Binary Search dijalankan.',
                        'items' => [
                            'Untuk contoh ini, data diurutkan dari nilai terkecil ke terbesar.',
                            'Jika data belum terurut, urutkan terlebih dahulu atau gunakan Sequential Search.',
                            'Arah pengurangan area pencarian harus sesuai dengan urutan data.',
                        ],
                    ],
                    [
                        'title' => 'Cara Kerja Binary Search',
                        'type' => 'steps',
                        'intro' => 'Cari target 25 pada data terurut [5, 10, 15, 20, 25, 30, 35]. Indeks dimulai dari 0 dan mid dihitung dengan floor((low + high) / 2).',
                        'items' => [
                            ['title' => 'Langkah 1: low=0, high=6, mid=3', 'detail' => 'Nilai di indeks 3 adalah 20. Karena 25 lebih besar dari 20, buang indeks 0 sampai 3. Area berikutnya memiliki low=4 dan high=6.'],
                            ['title' => 'Langkah 2: low=4, high=6, mid=5', 'detail' => 'Nilai di indeks 5 adalah 30. Karena 25 lebih kecil dari 30, buang indeks 5 sampai 6. Area berikutnya memiliki low=4 dan high=4.'],
                            ['title' => 'Langkah 3: low=4, high=4, mid=4', 'detail' => 'Nilai di indeks 4 adalah 25. Nilai sama dengan target, jadi target ditemukan pada indeks 4 atau posisi ke-5.'],
                        ],
                        'outro' => 'Setiap perbandingan menghilangkan sekitar separuh area yang masih mungkin berisi target.',
                    ],
                    [
                        'title' => 'Ilustrasi Area Pencarian',
                        'type' => 'binary_illustration',
                        'intro' => 'Target: 25. Badge hijau menunjukkan area yang masih diperiksa, sedangkan badge lainnya sudah dieliminasi.',
                        'values' => ['5', '10', '15', '20', '25', '30', '35'],
                        'states' => [
                            ['low' => 0, 'high' => 6, 'mid' => 3, 'remaining' => [4, 5, 6], 'description' => 'Nilai tengah 20 lebih kecil dari 25; lanjut ke kanan.'],
                            ['low' => 4, 'high' => 6, 'mid' => 5, 'remaining' => [4], 'description' => 'Nilai tengah 30 lebih besar dari 25; lanjut ke kiri dalam area ini.'],
                            ['low' => 4, 'high' => 4, 'mid' => 4, 'remaining' => [4], 'description' => 'Nilai tengah 25 cocok dengan target.'],
                        ],
                    ],
                    [
                        'title' => 'Pseudocode',
                        'type' => 'code',
                        'language' => 'Pseudocode',
                        'code' => "PROCEDURE BinarySearch(data, target)\n    low = 0\n    high = LENGTH(data) - 1\n\n    WHILE low <= high\n        mid = FLOOR((low + high) / 2)\n\n        IF data[mid] = target THEN\n            RETURN mid\n        ELSE IF data[mid] < target THEN\n            low = mid + 1\n        ELSE\n            high = mid - 1\n        END IF\n    END WHILE\n\n    RETURN -1  // target tidak ditemukan\nEND PROCEDURE",
                    ],
                    [
                        'title' => 'Contoh Python',
                        'type' => 'code',
                        'language' => 'Python',
                        'code' => "def binary_search(data, target):\n    # Tetapkan batas awal dan akhir area pencarian\n    low = 0\n    high = len(data) - 1\n\n    # Ulangi selama area pencarian masih ada\n    while low <= high:\n        # Ambil indeks tengah dari area yang tersisa\n        mid = (low + high) // 2\n\n        if data[mid] == target:\n            return mid\n        elif data[mid] < target:\n            # Target lebih besar, cari di bagian kanan\n            low = mid + 1\n        else:\n            # Target lebih kecil, cari di bagian kiri\n            high = mid - 1\n\n    # Target tidak ditemukan\n    return -1\n\ndata = [5, 10, 15, 20, 25, 30, 35]\ntarget = 25\nposition = binary_search(data, target)\n\nif position != -1:\n    print(f\"Target ditemukan pada indeks {position}\")\nelse:\n    print(\"Target tidak ditemukan\")",
                    ],
                    [
                        'title' => 'Contoh Kasus',
                        'type' => 'paragraphs',
                        'paragraphs' => [
                            'Sistem perpustakaan memiliki daftar nomor inventaris buku yang sudah diurutkan. Saat petugas mencari nomor tertentu, sistem dapat memeriksa nomor tengah terlebih dahulu. Jika target lebih besar, bagian kiri tidak perlu diperiksa lagi; jika lebih kecil, bagian kanan dapat diabaikan.',
                        ],
                    ],
                    [
                        'title' => 'Kelebihan',
                        'type' => 'list',
                        'items' => [
                            'Sangat efisien untuk mencari dalam data terurut yang jumlahnya besar.',
                            'Setiap langkah mengurangi area pencarian secara drastis.',
                            'Versi iteratif hanya memerlukan sedikit ruang tambahan.',
                        ],
                    ],
                    [
                        'title' => 'Kekurangan',
                        'type' => 'list',
                        'items' => [
                            'Data harus dalam keadaan terurut.',
                            'Jika data sering berubah dan harus selalu diurutkan, persiapan pengurutan menambah pekerjaan.',
                            'Logikanya lebih rumit dibandingkan Sequential Search, terutama saat memperbarui batas low dan high.',
                        ],
                    ],
                    [
                        'title' => 'Kompleksitas',
                        'type' => 'paragraphs',
                        'paragraphs' => [
                            'Sequential Search dapat memeriksa satu per satu hingga n elemen. Binary Search membagi area menjadi dua pada setiap langkah. Karena itu, untuk data yang sangat banyak dan sudah terurut, jumlah langkah Binary Search bertambah jauh lebih lambat, dengan kompleksitas waktu O(log n).',
                            'Sebagai gambaran, 1.024 data dapat dipersempit menjadi sekitar 10 kali pembagian dua, bukan diperiksa satu per satu. Dalam implementasi iteratif di atas, ruang tambahan yang digunakan adalah O(1).',
                        ],
                    ],
                    [
                        'title' => 'Perbandingan Sequential Search dan Binary Search',
                        'type' => 'table',
                        'headers' => ['Aspek', 'Sequential Search', 'Binary Search'],
                        'rows' => [
                            ['Kondisi data', 'Boleh belum terurut.', 'Harus sudah terurut.'],
                            ['Cara pencarian', 'Memeriksa elemen satu per satu.', 'Memeriksa nilai tengah lalu membuang separuh area.'],
                            ['Kecepatan', 'Dapat lambat jika data banyak.', 'Cepat untuk data besar yang sudah terurut.'],
                            ['Kompleksitas waktu terburuk', 'O(n).', 'O(log n).'],
                            ['Kemudahan implementasi', 'Lebih sederhana.', 'Perlu mengelola low, high, dan mid dengan benar.'],
                            ['Penggunaan', 'Data kecil, belum terurut, atau jarang dicari.', 'Data besar yang sudah terurut dan sering dicari.'],
                        ],
                    ],
                    [
                        'title' => 'Rangkuman',
                        'type' => 'list',
                        'items' => [
                            'Binary Search mencari dengan membagi dua area pencarian.',
                            'Data harus terurut agar arah pencarian dapat ditentukan.',
                            'low dan high membatasi area; mid adalah indeks tengahnya.',
                            'Kompleksitas waktu Binary Search adalah O(log n).',
                        ],
                    ],
                    [
                        'title' => 'Pertanyaan Refleksi',
                        'type' => 'list',
                        'items' => [
                            'Mengapa Binary Search tidak dapat langsung digunakan pada data yang belum terurut?',
                            'Pada langkah pertama contoh, mengapa indeks 0 sampai 3 dieliminasi?',
                            'Dalam situasi apa Sequential Search lebih tepat dipilih daripada Binary Search?',
                        ],
                    ],
                ],
            ],
        ];
    }
}
