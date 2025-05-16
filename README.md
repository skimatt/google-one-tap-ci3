# Autentikasi Google One Tap dengan ID Token (CodeIgniter 3)

Metode ini menggunakan **Google Identity Services (GIS)** dengan pendekatan **One Tap Login** dan **ID Token (OAuth 2.0)** untuk mengautentikasi pengguna. Ini memungkinkan pengguna login atau register hanya dengan akun Google tanpa perlu mengetikkan password.

---

## 🔧 Alur Kerja

1. **Frontend** memuat skrip Google One Tap (`gsi.client.js`) yang menampilkan login popup.
2. Setelah user memilih akun Google, Google mengirimkan `credential` berupa **ID Token (JWT)** ke frontend.
3. **Frontend** mengirimkan ID Token ke backend (CodeIgniter).
4. **Backend** memverifikasi ID Token ke endpoint Google:  
   `https://oauth2.googleapis.com/tokeninfo?id_token=...`
5. Setelah verifikasi berhasil:
   - Jika email sudah ada → login.
   - Jika belum → otomatis **register dan login**.

---

## 📦 Contoh Kode (Backend: CodeIgniter 3)

```php
public function google_callback() {
    $id_token = $this->input->post('credential') ?? $_POST['credential'] ?? null;

    if (!$id_token) {
        $this->session->set_flashdata('error', 'Login gagal. Token tidak ditemukan.');
        redirect('auth/login');
    }

    $client_id = 'YOUR_GOOGLE_CLIENT_ID';
    $response = file_get_contents("https://oauth2.googleapis.com/tokeninfo?id_token=" . $id_token);
    $payload = json_decode($response, true);

    if (!$payload || $payload['aud'] !== $client_id) {
        $this->session->set_flashdata('error', 'Token tidak valid.');
        redirect('auth/login');
    }

    $email = $payload['email'];
    $nama = $payload['name'];

    $user = $this->db->get_where('tb_users', ['email' => $email])->row();

    if ($user) {
        if ($user->role !== 'pasien') {
            $this->session->set_flashdata('error', 'Akun bukan pasien.');
            redirect('auth/login');
        }
        $this->session->set_userdata([
            'uuid' => $user->uuid,
            'email' => $user->email,
            'nama' => $user->nama,
            'role' => $user->role
        ]);
        redirect('pasien');
    } else {
        $uuid = $this->generate_uuid();
        $user_data = [
            'uuid' => $uuid,
            'email' => $email,
            'password' => null,
            'nama' => $nama,
            'role' => 'pasien',
            'is_active' => 1,
            'registered_with' => 'google'
        ];
        $pasien_data = ['uuid' => $uuid, 'nama' => $nama];

        $this->User_model->insert_user($user_data);
        $this->User_model->insert_pasien($pasien_data);

        $this->session->set_userdata([
            'uuid' => $uuid,
            'email' => $email,
            'nama' => $nama,
            'role' => 'pasien'
        ]);
        redirect('pasien');
    }
}
````

---

## 🛠️ Perubahan Database

Pastikan kolom `password` di tabel `tb_users` bisa bernilai `NULL`:

```sql
ALTER TABLE tb_users MODIFY password VARCHAR(255) NULL;
```

Tambahkan juga kolom tracking jenis registrasi (opsional tapi disarankan):

```sql
ALTER TABLE tb_users ADD registered_with VARCHAR(50) DEFAULT 'manual';
```

---

## ✅ Keunggulan Metode Ini

* Tidak perlu password lokal.
* Mudah untuk pengguna (cukup klik akun Google).
* Registrasi otomatis hanya jika belum terdaftar.
* Aman: token diverifikasi langsung ke Google.

---

## 📚 Referensi

* [Google Identity Services – One Tap](https://developers.google.com/identity/gsi/web/guides/overview)
* [Verifying ID Tokens](https://developers.google.com/identity/sign-in/web/backend-auth)


