public function google_callback() {
    if ($this->input->post('credential')) {
        $id_token = $this->input->post('credential');
         $this->security->csrf_verify = FALSE;
    } elseif (isset($_POST['credential'])) {
        $id_token = $_POST['credential'];
    } else {
        $id_token = null;
    }

    if (!$id_token) {
        $this->session->set_flashdata('error', 'Login gagal. Token tidak ditemukan.');
        redirect('auth/login');
    }

    $client_id = '723388901536-bsglfjcmqch880c1gppvtfsb7uusi5v8.apps.googleusercontent.com';
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
            $this->session->set_flashdata('error', 'Akun Anda bukan pasien. Login Google hanya untuk pasien.');
            redirect('auth/login');
        }

        $session_data = [
            'uuid' => $user->uuid,
            'email' => $user->email,
            'nama' => $user->nama,
            'role' => $user->role
        ];
        $this->session->set_userdata($session_data);
        redirect('pasien');
    } else {
        // Daftarkan sebagai pasien baru
        $uuid = $this->generate_uuid();
        $user_data = [
            'uuid' => $uuid,
            'email' => $email,
            'password' => null,
            'nama' => $nama,
            'role' => 'pasien',
            'is_active' => 1
        ];
        $pasien_data = [
            'uuid' => $uuid,
            'nama' => $nama
        ];
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
