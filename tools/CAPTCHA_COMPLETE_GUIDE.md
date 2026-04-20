# CAPTCHA Complete Guide (MindTrack)

## Overview

This guide documents the CAPTCHA feature currently used in MindTrack and how to reproduce the same behavior in the web app.

Current desktop implementation is based on a custom visual CAPTCHA utility and is used to protect:
- login attempts
- password reset code requests

Primary files:
- `src/main/java/utils/CaptchaUtil.java`
- `src/main/java/controllers/LoginController.java`
- `src/main/java/controllers/ForgotPasswordController.java`

---

## Current Behavior (JavaFX)

### CAPTCHA generation
- Code length: **6 characters**
- Charset: `ABCDEFGHJKLMNPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz23456789`
- Excludes ambiguous characters (for example `O`, `0`, `I`, `1`)
- Code is regenerated:
  - on page init
  - on explicit refresh
  - after invalid attempt (in reset flow)

### CAPTCHA rendering
`CaptchaUtil.drawCaptcha(Canvas)` draws:
- light background and border
- random noise lines
- noise dots
- each character with random:
  - angle
  - slight position offset
  - color
  - size

### CAPTCHA validation
- Validation is **case-sensitive**
- Logic: `input != null && input.trim().equals(code)`
- Invalid input blocks protected action

---

## End-to-End Flow

### Login flow
1. Login screen initializes CAPTCHA.
2. User enters email/password + CAPTCHA.
3. CAPTCHA is validated before authentication logic continues.
4. If invalid, login is blocked and user must retry with refreshed CAPTCHA.

### Password reset flow
1. Forgot-password screen initializes CAPTCHA.
2. User enters email + CAPTCHA.
3. `handleSendCode()` validates email and CAPTCHA first.
4. If CAPTCHA is invalid:
   - error message shown (`Invalid CAPTCHA`)
   - input cleared
   - CAPTCHA refreshed
5. If valid, reset-code request continues.

---

## Security Rules to Keep

1. CAPTCHA must be checked before expensive/sensitive operations.
2. CAPTCHA code must not be exposed in logs or client responses.
3. CAPTCHA should be refreshed after invalid attempts.
4. CAPTCHA should be treated as one challenge per step, not a permanent token.
5. Error messaging should remain generic (`Invalid CAPTCHA`).

---

## Edge Cases

- Empty input: invalid.
- Whitespace input: trimmed then validated.
- Expired/old visual challenge after refresh: invalid until user enters latest value.
- Multi-tab behavior: latest challenge should be used; old tab may fail and require refresh.
- Repeated failures: always regenerate challenge to reduce replay.

---

## Symfony Porting Notes (Same Behavior)

To mirror Java behavior in Symfony:

### Recommended structure
- `src/Service/CaptchaService.php`
  - generate 6-char code
  - store/retrieve from session
  - verify case-sensitive
  - clear/regenerate
- `src/Controller/CaptchaController.php`
  - endpoint for image
  - endpoint for refresh
- enforce validation in:
  - login action/authenticator
  - forgot-password request action

### Session model
- Store active CAPTCHA code in server-side session.
- Compare submitted value with current session value.
- On invalid submit: regenerate challenge.
- On success: consume or rotate challenge.

### UI model (Twig)
- CAPTCHA image block
- input field
- refresh button
- error area for invalid CAPTCHA

---

## Mapping Table (Desktop -> Web)

| Desktop (JavaFX) | Symfony (Web) |
|---|---|
| `CaptchaUtil.generateCaptcha()` | `CaptchaService::generate()` |
| `CaptchaUtil.drawCaptcha(Canvas)` | `CaptchaController` image response |
| `CaptchaUtil.verify(input)` | `CaptchaService::verify()` |
| `refreshCaptcha()` in controllers | refresh route + client-side image reload |
| validation in `LoginController` / `ForgotPasswordController` | validation in authenticator/controller actions |

---

## Implementation Checklist

### Desktop parity checklist
- [ ] 6-character challenge
- [ ] same readable charset
- [ ] case-sensitive verification
- [ ] visual noise in image
- [ ] refresh action available
- [ ] invalid attempt regenerates challenge
- [ ] login CAPTCHA enforced
- [ ] password-reset request CAPTCHA enforced

### Web readiness checklist
- [ ] session-backed challenge storage
- [ ] cache-busting on CAPTCHA image
- [ ] generic invalid message
- [ ] no sensitive logging
- [ ] route-level rate limiting (recommended)

---

## Quick Reference

### Where CAPTCHA exists today
- `src/main/java/utils/CaptchaUtil.java`
- `src/main/java/controllers/LoginController.java`
- `src/main/java/controllers/ForgotPasswordController.java`

### What to preserve in Symfony
- 6-char custom challenge
- case-sensitive match
- refresh + retry flow
- pre-auth/pre-reset validation gate

---

## Final Note

Your current CAPTCHA is a custom anti-bot protection layer integrated into app flows, not a third-party service. For web deployment, you can keep this custom model for parity, then later switch to reCAPTCHA/Turnstile if you need stronger bot resistance at scale.

