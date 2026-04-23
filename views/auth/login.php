<section class="login-page">
    <div class="login-card">
        <div class="login-brand" style="flex-direction: column; gap: 8px;">
            <img src="/ACEP%20logo%20new%201.png" alt="Apex Fire" style="height: 60px; width: auto;">
            <span class="login-brand-name">Apex</span>
        </div>
        
        <h1 class="login-title">Fire Panel Monitoring</h1>
        <p class="login-subtitle">Monitor health across subscribed sites. Panels push status data every 12 minutes.</p>

        <?php if (!empty($error)): ?>
            <div class="flash error" role="alert">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
                <?= h($error) ?>
            </div>
        <?php endif; ?>

        <form class="stack" method="post" action="/login" style="margin-top: 24px;">
            <div class="form-row">
                <label for="login-email">Email address</label>
                <input type="email" id="login-email" name="email" autocomplete="username" placeholder="you@company.com" required>
            </div>
            <div class="form-row">
                <label for="login-password">Password</label>
                <input type="password" id="login-password" name="password" autocomplete="current-password" placeholder="Enter your password" required>
            </div>
            <button type="submit" style="margin-top: 8px; width: 100%; justify-content: center;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></svg>
                Sign In to Dashboard
            </button>
        </form>

        <div class="trust-badges">
            <div class="trust-badge">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                <span>Encrypted</span>
            </div>
            <div class="trust-badge">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                <span>Real-time</span>
            </div>
            <div class="trust-badge">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                <span>99.9% SLA</span>
            </div>
        </div>
    </div>
</section>