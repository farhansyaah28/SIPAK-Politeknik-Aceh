<?php
// SIIPAK - Profile Edit Modal for Renters
$id_penyewa_modal = $_SESSION['user_id'] ?? 0;
$stmt_modal = $pdo->prepare("SELECT * FROM penyewa WHERE id_penyewa = :id");
$stmt_modal->execute([':id' => $id_penyewa_modal]);
$user_modal = $stmt_modal->fetch();
?>
<?php if ($user_modal): ?>
<!-- Modal Edit Profil Penyewa -->
<div id="profileEditModal" class="hidden fixed inset-0 bg-black/60 backdrop-blur-xs flex items-center justify-center z-[150] p-md">
    <div class="bg-white rounded-2xl max-w-sm w-full overflow-hidden border border-outline-variant shadow-lg">
        <div class="bg-primary text-white p-md flex items-center justify-between">
            <div class="flex items-center gap-xs">
                <span class="material-symbols-outlined text-warning-amber">manage_accounts</span>
                <h5 class="text-xs font-bold text-white">Edit Profil Saya</h5>
            </div>
            <button type="button" onclick="closeProfileModal()" class="text-white hover:text-warning-amber transition-colors">
                <span class="material-symbols-outlined text-sm">close</span>
            </button>
        </div>
        <form method="POST" action="" class="m-0">
            <input type="hidden" name="update_profile_modal" value="1">
            <div class="p-md space-y-sm text-xs max-h-[70vh] overflow-y-auto">
                <div class="space-y-base">
                    <label for="modal_nama" class="font-semibold text-primary block">Nama Lengkap *</label>
                    <input type="text" name="nama" id="modal_nama" class="w-full py-1.5 px-md rounded-lg border border-outline-variant focus:border-primary focus:ring-1 focus:ring-primary/20 bg-surface-container-lowest text-xs text-on-surface" required value="<?= htmlspecialchars($user_modal['nama']) ?>">
                </div>

                <div class="space-y-base">
                    <label for="modal_email" class="font-semibold text-primary block">Alamat Email *</label>
                    <input type="email" name="email" id="modal_email" class="w-full py-1.5 px-md rounded-lg border border-outline-variant focus:border-primary focus:ring-1 focus:ring-primary/20 bg-surface-container-lowest text-xs text-on-surface" required value="<?= htmlspecialchars($user_modal['email']) ?>">
                </div>

                <div class="grid grid-cols-2 gap-sm">
                    <div class="space-y-base">
                        <label for="modal_no_telepon" class="font-semibold text-primary block">Nomor HP/WA *</label>
                        <input type="text" name="no_telepon" id="modal_no_telepon" class="w-full py-1.5 px-md rounded-lg border border-outline-variant focus:border-primary focus:ring-1 focus:ring-primary/20 bg-surface-container-lowest text-xs text-on-surface" required value="<?= htmlspecialchars($user_modal['no_telepon']) ?>">
                    </div>
                    <div class="space-y-base">
                        <label for="modal_instansi" class="font-semibold text-primary block">Nama Instansi</label>
                        <input type="text" name="instansi" id="modal_instansi" class="w-full py-1.5 px-md rounded-lg border border-outline-variant focus:border-primary focus:ring-1 focus:ring-primary/20 bg-surface-container-lowest text-xs text-on-surface" value="<?= htmlspecialchars($user_modal['instansi']) ?>" placeholder="HMIF / Perorangan">
                    </div>
                </div>

                <div class="space-y-base">
                    <label for="modal_alamat" class="font-semibold text-primary block">Alamat Domisili *</label>
                    <textarea name="alamat" id="modal_alamat" rows="2" class="w-full p-md rounded-lg border border-outline-variant focus:border-primary focus:ring-1 focus:ring-primary/20 bg-surface-container-lowest text-xs text-on-surface" required><?= htmlspecialchars($user_modal['alamat']) ?></textarea>
                </div>

                <div class="space-y-base pt-xs border-t border-outline-variant/60">
                    <label for="modal_password" class="font-semibold text-primary block">Ubah Password (Kosongkan jika tidak diubah)</label>
                    <input type="password" name="password" id="modal_password" class="w-full py-1.5 px-md rounded-lg border border-outline-variant focus:border-primary focus:ring-1 focus:ring-primary/20 bg-surface-container-lowest text-xs text-on-surface" placeholder="Password baru...">
                </div>
            </div>
            <div class="px-md py-2.5 bg-surface-container-low border-t border-outline-variant/40 flex justify-end gap-sm">
                <button type="button" onclick="closeProfileModal()" class="px-md py-1.5 border border-outline text-on-surface-variant text-[11px] rounded-lg hover:bg-surface-container-high transition-colors">Batal</button>
                <button type="submit" class="px-md py-1.5 bg-primary text-white font-bold text-[11px] rounded-lg hover:shadow-md active:scale-95 transition-all">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<script>
function openProfileModal() {
    const modal = document.getElementById('profileEditModal');
    if (modal) {
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }
}

function closeProfileModal() {
    const modal = document.getElementById('profileEditModal');
    if (modal) {
        modal.classList.remove('flex');
        modal.classList.add('hidden');
    }
}
</script>
