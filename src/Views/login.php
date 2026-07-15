<?= $this->extend('exAuth\layout') ?>

<?= $this->section('title') ?>Login<?= $this->endSection() ?>

<?= $this->section('main') ?>

<div class="container d-flex justify-content-center p-5">
    <div class="card col-12 col-md-5 shadow-sm">
        <div class="card-body">
            <h5 class="card-title mb-4">Login</h5>

            <?php if (session('error') !== null) : ?>
                <div class="alert alert-danger"><?= esc(session('error')) ?></div>
            <?php endif ?>
            <?php if (session('message') !== null) : ?>
                <div class="alert alert-success"><?= esc(session('message')) ?></div>
            <?php endif ?>

            <form action="<?= url_to('login') ?>" method="post">
                <?= csrf_field() ?>

                <div class="mb-3">
                    <label class="form-label" for="login">Email or Username</label>
                    <input type="text" class="form-control" id="login" name="login" inputmode="email" autocomplete="username" value="<?= old('login') ?>" required>
                </div>

                <div class="mb-3">
                    <label class="form-label" for="password">Password</label>
                    <input type="password" class="form-control" id="password" name="password" autocomplete="current-password" required>
                </div>

                <div class="d-grid mb-3">
                    <button type="submit" class="btn btn-primary">Login</button>
                </div>

                <p class="text-center mb-1"><a href="<?= url_to('forgot-password') ?>">Forgot your password?</a></p>
                <p class="text-center mb-1"><a href="<?= url_to('magic-link') ?>">Login with magic link</a></p>
                <p class="text-center">Need an account? <a href="<?= url_to('register') ?>">Register</a></p>
            </form>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
