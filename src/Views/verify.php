<?= $this->extend('exAuth\layout') ?>

<?= $this->section('title') ?>Verify Account<?= $this->endSection() ?>

<?= $this->section('main') ?>

<div class="container d-flex justify-content-center p-5">
    <div class="card col-12 col-md-5 shadow-sm">
        <div class="card-body">
            <h5 class="card-title mb-4">Verify Your Account</h5>

            <?php if (session('error') !== null) : ?>
                <div class="alert alert-danger"><?= esc(session('error')) ?></div>
            <?php endif ?>

            <form action="<?= url_to('verify') ?>" method="post">
                <?= csrf_field() ?>
                <input type="hidden" name="token" value="<?= esc($token ?? '') ?>">

                <div class="d-grid mb-3">
                    <button type="submit" class="btn btn-primary">Verify</button>
                </div>

                <p class="text-center"><a href="<?= url_to('login') ?>">Back to login</a></p>
            </form>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
