<div class="auth-divider"><span>hoặc</span></div>
<div class="auth-social"><?php foreach(['google'=>'Google','facebook'=>'Facebook'] as $provider=>$label): ?>
<?php if(oauth_configured($provider)): ?><a class="auth-provider <?= $provider ?>" href="<?= BASE_URL ?>/auth/oauth.php?provider=<?= $provider ?>"><span aria-hidden="true"><?= $provider==='google'?'G':'f' ?></span><?= $label ?></a>
<?php else: ?><button type="button" class="auth-provider <?= $provider ?>" disabled title="<?= $label ?> chưa kết nối" aria-label="<?= $label ?> chưa kết nối"><span aria-hidden="true"><?= $provider==='google'?'G':'f' ?></span><?= $label ?></button>
<?php endif; endforeach; ?></div>
<?php $unavailable=array_filter(['google'=>'Google','facebook'=>'Facebook'],fn($label,$provider)=>!oauth_configured($provider),ARRAY_FILTER_USE_BOTH); if($unavailable): ?><p class="auth-status"><?= sanitize(implode(' và ',$unavailable)) ?> chưa kết nối.</p><?php endif; ?>
