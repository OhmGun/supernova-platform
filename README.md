# Supernova Platform Provisioning

Platform provisioning & billing untuk OLT ZTE GPON dan MikroTik PPPoE.

## Fitur
- **Broadband**: generate script OLT ZTE (register ONU by SN, service port, config layanan PPPoE)
- **MikroTik PPPoE**: tambah secret PPPoE via API MikroTik (auto-generate user ID unik, cek duplikat)
- **Pelanggan**: kelola data pelanggan (CRUD), sinkronisasi Broadband ⇄ Pelanggan (auto-update), status Aktif/Isolir/Cabut
- **Monitoring**: status online/offline MikroTik, scan offline OLT
- **Admin**: pengaturan kredensial, pengguna system, audit log

## Stack
- PHP 8 + MySQL (XAMPP)
- phpseclib (SSH ke OLT ZTE)
- RouterOS API (MikroTik)