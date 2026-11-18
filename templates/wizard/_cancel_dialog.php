<dialog id="cancel-dialog" class="dialog" aria-labelledby="cancel-title">
    <div class="dialog__panel">
        <div class="dialog__icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" width="26" height="26" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="M12 9v4"/><path d="M12 17h.01"/><path d="M10.3 3.9 2.6 17.2A2 2 0 0 0 4.3 20h15.4a2 2 0 0 0 1.7-2.8L13.7 3.9a2 2 0 0 0-3.4 0Z"/></svg>
        </div>
        <h2 id="cancel-title">Batalkan pengisian?</h2>
        <p>Data yang sudah Anda isi pada sesi ini akan dihapus dan Anda akan kembali ke langkah awal.</p>
        <div class="dialog__actions">
            <button type="button" class="btn btn--ghost" data-dialog-close>Lanjut mengisi</button>
            <form method="post" action="<?= url('/reset') ?>">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn--danger">Ya, batalkan</button>
            </form>
        </div>
    </div>
</dialog>
