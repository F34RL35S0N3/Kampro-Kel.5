-- ==========================================================================
-- SIMLAP-Riset — skema MySQL/MariaDB (InnoDB, utf8mb4)
-- Target produksi sesuai PRD §8.1.
-- ==========================================================================

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS laporan (
    id                      BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    reg_no                  VARCHAR(20)     NOT NULL,
    ketua_nama              VARCHAR(100)    NOT NULL,
    ketua_jabatan           VARCHAR(100)    NOT NULL,
    ketua_nip               CHAR(18)        NOT NULL,
    ketua_fakultas          VARCHAR(150)    NOT NULL,
    ketua_kelompok_keilmuan VARCHAR(150)    NOT NULL,
    ketua_telp              VARCHAR(20)     NOT NULL,
    ketua_email             VARCHAR(254)    NOT NULL,
    lama_minggu             SMALLINT UNSIGNED NOT NULL,
    biaya_rupiah            BIGINT UNSIGNED NOT NULL,
    jenis_penelitian        VARCHAR(20)     NOT NULL,
    cpl_prodi               TEXT            NOT NULL,
    tema_rip                VARCHAR(500)    NOT NULL,
    mitra                   VARCHAR(300)    DEFAULT NULL,
    potensi_adopsi          TEXT            NOT NULL,
    status                  VARCHAR(20)     NOT NULL DEFAULT 'DIAJUKAN',
    requires_dual_approval  TINYINT(1)      NOT NULL DEFAULT 0,
    payload_hash            CHAR(64)        NOT NULL,
    idem_key                VARCHAR(64)     NOT NULL,
    ip_hash                 CHAR(64)        DEFAULT NULL,
    created_at              DATETIME        NOT NULL,
    updated_at              DATETIME        NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_laporan_reg_no (reg_no),
    UNIQUE KEY uq_laporan_idem_key (idem_key),
    KEY idx_laporan_status (status),
    KEY idx_laporan_created (created_at),
    KEY idx_laporan_nip_created (ketua_nip, created_at),
    KEY idx_laporan_payload_created (payload_hash, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS laporan_anggota (
    id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    laporan_id  BIGINT UNSIGNED NOT NULL,
    no_urut     TINYINT UNSIGNED NOT NULL,
    nama        VARCHAR(100)    NOT NULL,
    nip_npm     VARCHAR(18)     NOT NULL,
    bidang_kelas VARCHAR(100)   NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_anggota_urut (laporan_id, no_urut),
    KEY idx_anggota_laporan (laporan_id),
    CONSTRAINT fk_anggota_laporan FOREIGN KEY (laporan_id)
        REFERENCES laporan (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS laporan_luaran (
    id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    laporan_id  BIGINT UNSIGNED NOT NULL,
    no_urut     TINYINT UNSIGNED NOT NULL,
    jenis       VARCHAR(30)     NOT NULL,
    nama        VARCHAR(200)    DEFAULT NULL,
    jumlah      TINYINT UNSIGNED NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_luaran_urut (laporan_id, no_urut),
    KEY idx_luaran_laporan (laporan_id),
    CONSTRAINT fk_luaran_laporan FOREIGN KEY (laporan_id)
        REFERENCES laporan (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS admin_users (
    id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    username      VARCHAR(50)     NOT NULL,
    password_hash VARCHAR(255)    NOT NULL,
    role          VARCHAR(20)     NOT NULL DEFAULT 'reviewer',
    is_active     TINYINT(1)      NOT NULL DEFAULT 1,
    created_at    DATETIME        NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_admin_username (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS approvals (
    id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    laporan_id  BIGINT UNSIGNED NOT NULL,
    admin_id    BIGINT UNSIGNED NOT NULL,
    decision    VARCHAR(10)     NOT NULL,
    created_at  DATETIME        NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_approval_admin (laporan_id, admin_id),
    KEY idx_approval_laporan (laporan_id),
    CONSTRAINT fk_approval_laporan FOREIGN KEY (laporan_id)
        REFERENCES laporan (id) ON DELETE CASCADE,
    CONSTRAINT fk_approval_admin FOREIGN KEY (admin_id)
        REFERENCES admin_users (id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS idempotency (
    idem_key    VARCHAR(64)     NOT NULL,
    laporan_id  BIGINT UNSIGNED DEFAULT NULL,
    payload_hash CHAR(64)       NOT NULL,
    created_at  DATETIME        NOT NULL,
    PRIMARY KEY (idem_key),
    KEY idx_idempotency_laporan (laporan_id),
    CONSTRAINT fk_idempotency_laporan FOREIGN KEY (laporan_id)
        REFERENCES laporan (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS counters (
    name    VARCHAR(64)     NOT NULL,
    value   BIGINT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS rate_limits (
    id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    bucket      VARCHAR(191)    NOT NULL,
    created_at  DATETIME        NOT NULL,
    PRIMARY KEY (id),
    KEY idx_rate_bucket (bucket, created_at),
    KEY idx_rate_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS audit_log (
    id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    event       VARCHAR(64)     NOT NULL,
    laporan_id  BIGINT UNSIGNED DEFAULT NULL,
    actor       VARCHAR(50)     DEFAULT NULL,
    ip_hash     CHAR(64)        DEFAULT NULL,
    created_at  DATETIME        NOT NULL,
    PRIMARY KEY (id),
    KEY idx_audit_event (event, created_at),
    KEY idx_audit_laporan (laporan_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
