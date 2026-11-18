-- ==========================================================================
-- SIMLAP-Riset — skema SQLite (mode demo / pengembangan)
-- ==========================================================================

PRAGMA foreign_keys = OFF;

CREATE TABLE IF NOT EXISTS laporan (
    id                      INTEGER PRIMARY KEY AUTOINCREMENT,
    reg_no                  TEXT NOT NULL UNIQUE,
    ketua_nama              TEXT NOT NULL,
    ketua_jabatan           TEXT NOT NULL,
    ketua_nip               TEXT NOT NULL,
    ketua_fakultas          TEXT NOT NULL,
    ketua_kelompok_keilmuan TEXT NOT NULL,
    ketua_telp              TEXT NOT NULL,
    ketua_email             TEXT NOT NULL,
    lama_minggu             INTEGER NOT NULL,
    biaya_rupiah            INTEGER NOT NULL,
    jenis_penelitian        TEXT NOT NULL,
    cpl_prodi               TEXT NOT NULL,
    tema_rip                TEXT NOT NULL,
    mitra                   TEXT,
    potensi_adopsi          TEXT NOT NULL,
    status                  TEXT NOT NULL DEFAULT 'DIAJUKAN',
    requires_dual_approval  INTEGER NOT NULL DEFAULT 0,
    payload_hash            TEXT NOT NULL,
    idem_key                TEXT NOT NULL UNIQUE,
    ip_hash                 TEXT,
    created_at              TEXT NOT NULL,
    updated_at              TEXT NOT NULL
);

CREATE INDEX IF NOT EXISTS idx_laporan_status ON laporan (status);
CREATE INDEX IF NOT EXISTS idx_laporan_created ON laporan (created_at);
CREATE INDEX IF NOT EXISTS idx_laporan_nip_created ON laporan (ketua_nip, created_at);
CREATE INDEX IF NOT EXISTS idx_laporan_payload_created ON laporan (payload_hash, created_at);

CREATE TABLE IF NOT EXISTS laporan_anggota (
    id          INTEGER PRIMARY KEY AUTOINCREMENT,
    laporan_id  INTEGER NOT NULL REFERENCES laporan (id) ON DELETE CASCADE,
    no_urut     INTEGER NOT NULL,
    nama        TEXT NOT NULL,
    nip_npm     TEXT NOT NULL,
    bidang_kelas TEXT NOT NULL,
    UNIQUE (laporan_id, no_urut)
);

CREATE INDEX IF NOT EXISTS idx_anggota_laporan ON laporan_anggota (laporan_id);

CREATE TABLE IF NOT EXISTS laporan_luaran (
    id          INTEGER PRIMARY KEY AUTOINCREMENT,
    laporan_id  INTEGER NOT NULL REFERENCES laporan (id) ON DELETE CASCADE,
    no_urut     INTEGER NOT NULL,
    jenis       TEXT NOT NULL,
    nama        TEXT,
    jumlah      INTEGER NOT NULL,
    UNIQUE (laporan_id, no_urut)
);

CREATE INDEX IF NOT EXISTS idx_luaran_laporan ON laporan_luaran (laporan_id);

CREATE TABLE IF NOT EXISTS admin_users (
    id            INTEGER PRIMARY KEY AUTOINCREMENT,
    username      TEXT NOT NULL UNIQUE,
    password_hash TEXT NOT NULL,
    role          TEXT NOT NULL DEFAULT 'reviewer',
    is_active     INTEGER NOT NULL DEFAULT 1,
    created_at    TEXT NOT NULL
);

CREATE TABLE IF NOT EXISTS approvals (
    id          INTEGER PRIMARY KEY AUTOINCREMENT,
    laporan_id  INTEGER NOT NULL REFERENCES laporan (id) ON DELETE CASCADE,
    admin_id    INTEGER NOT NULL REFERENCES admin_users (id) ON DELETE RESTRICT,
    decision    TEXT NOT NULL,
    created_at  TEXT NOT NULL,
    UNIQUE (laporan_id, admin_id)
);

CREATE INDEX IF NOT EXISTS idx_approval_laporan ON approvals (laporan_id);

CREATE TABLE IF NOT EXISTS idempotency (
    idem_key    TEXT PRIMARY KEY,
    laporan_id  INTEGER REFERENCES laporan (id) ON DELETE SET NULL,
    payload_hash TEXT NOT NULL,
    created_at  TEXT NOT NULL
);

CREATE INDEX IF NOT EXISTS idx_idempotency_laporan ON idempotency (laporan_id);

CREATE TABLE IF NOT EXISTS counters (
    name    TEXT PRIMARY KEY,
    value   INTEGER NOT NULL DEFAULT 0
);

CREATE TABLE IF NOT EXISTS rate_limits (
    id          INTEGER PRIMARY KEY AUTOINCREMENT,
    bucket      TEXT NOT NULL,
    created_at  TEXT NOT NULL
);

CREATE INDEX IF NOT EXISTS idx_rate_bucket ON rate_limits (bucket, created_at);
CREATE INDEX IF NOT EXISTS idx_rate_created ON rate_limits (created_at);

CREATE TABLE IF NOT EXISTS audit_log (
    id          INTEGER PRIMARY KEY AUTOINCREMENT,
    event       TEXT NOT NULL,
    laporan_id  INTEGER,
    actor       TEXT,
    ip_hash     TEXT,
    created_at  TEXT NOT NULL
);

CREATE INDEX IF NOT EXISTS idx_audit_event ON audit_log (event, created_at);
CREATE INDEX IF NOT EXISTS idx_audit_laporan ON audit_log (laporan_id);

PRAGMA foreign_keys = ON;
