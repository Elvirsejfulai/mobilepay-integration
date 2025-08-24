# MobilePay App Payments Web – PHP integracija

Ovaj projekat pokazuje kako integrisati **MobilePay plaćanja** u PHP aplikaciju.  
Korisnik može platiti putem MobilePay aplikacije, a tvoj sistem automatski dobija obavijest o statusu putem webhook-a.

---

## 📂 Struktura

```
/mobilepay/
  config.php          # Konfiguracija i zajedničke funkcije
  create_payment.php  # Kreiranje nove uplate i preusmjeravanje korisnika
  redirect.php        # Povratna stranica nakon plaćanja
  webhook.php         # MobilePay šalje status uplate
  check_payment.php   # Ručna provjera statusa uplate
```

---

## ⚙️ 1. config.php

Ovdje postavljaš sve **ključeve i URL-ove**:

- `CLIENT_ID`, `CLIENT_SECRET` – dobijeni od MobilePay/Vipps portala  
- `OCP_APIM_SUBSCRIPTION_KEY` – subscription key  
- `PAYMENT_POINT_ID` – ID tvoje kase (Payment Point)  
- `REDIRECT_URI` – stranica gdje se korisnik vraća  
- `WEBHOOK_URL` – URL gdje MobilePay šalje status  

---

## 💳 2. create_payment.php

- Kada korisnik klikne **"Plati sa MobilePay"**, ovaj fajl:
  1. Uzima **access token**
  2. Kreira novu uplatu preko MobilePay API-ja
  3. Dobija `mobilePayAppRedirectUri` i preusmjerava korisnika u MobilePay aplikaciju

```php
$payload = [
  'amount' => 1250, // iznos u øre (12.50 DKK)
  'paymentPointId' => PAYMENT_POINT_ID,
  'reference' => 'ORDER-12345',
  'redirectUri' => REDIRECT_URI
];
```

---

## ↩️ 3. redirect.php

- Nakon plaćanja korisnik se vraća na ovaj URL  
- Možeš prikazati **“Hvala na uplati”** ili **“Plaćanje prekinuto”**  
- Konačan status ipak dolazi preko webhook-a

---

## 🔔 4. webhook.php

MobilePay automatski šalje status:

- `payment.reserved` → uplata rezervisana (ovdje radiš **capture**)  
- `payment.cancelled_by_user` → korisnik odustao  
- `payment.expired` → isteklo vrijeme  
- `transfer.succeeded` → novac prebačen na tvoj račun  

---

## 🔍 5. check_payment.php

- Ručno provjerava status određene uplate preko `paymentId`  
- Korisno za podršku ili debug  

Primjer:
```
https://tvoj-site.ba/mobilepay/check_payment.php?paymentId=<ID>
```

---

## 🛠️ Testiranje (Sandbox)

1. U `config.php` stavi:
   ```php
   define('SANDBOX', true);
   ```
2. Unesi sandbox ključeve iz Vipps/MobilePay portala  
3. Pokreni plaćanje i koristi MobilePay sandbox aplikaciju  
4. Status provjeri u `orders.json` ili preko `check_payment.php`

