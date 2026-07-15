<?= $this->extend('exAuth\layout') ?>

<?= $this->section('title') ?>Reset Password<?= $this->endSection() ?>

<?= $this->section('main') ?>

<div class="container d-flex justify-content-center p-5">
    <div class="card col-12 col-md-5 shadow-sm">
        <div class="card-body">
            <h5 class="card-title mb-4">Reset Password</h5>

            <?php if (session('error') !== null) : ?>
                <div class="alert alert-danger"><?= esc(session('error')) ?></div>
            <?php endif ?>

            <form action="<?= url_to('reset-password') ?>" method="post">
                <?= csrf_field() ?>
                <input type="hidden" name="token" value="<?= esc($token ?? '') ?>">

                <div class="mb-3">
                    <label class="form-label" for="password">New Password</label>
                    <input type="password" class="form-control" id="password" name="password" autocomplete="new-password" required>
                </div>

                <div class="d-grid mb-3">
                    <button type="submit" class="btn btn-primary">Reset Password</button>
                </div>

                <p class="text-center"><a href="<?= url_to('login') ?>">Back to login</a></p>
            </form>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
