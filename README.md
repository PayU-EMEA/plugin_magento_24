[**English version**][ext8]

# Moduł PayU dla Magento 2 w wersji 2.4

## UWAGA
**W związku ze zmianami opisanymy w [CHANGELOG][ext10] po aktualizacji wtyczki z wersji 1.X na 2.X należy wykonać ponowną konfigurację wtyczki.**

**Jeżeli masz jakiekolwiek pytania lub chcesz zgłosić błąd zapraszamy do kontaktu z naszym wsparciem pod adresem: tech@payu.pl.**

* Jeżeli używasz Magento w wersji 1.x proszę skorzystać z [pluginu dla wersji 1.x][ext0]
* Jeżeli używasz Magento w wersji >2.0.6, 2.1, 2.2 proszę skorzystać z [pluginu dla wersji >2.0.6, 2.1, 2.2][ext7]
* Jeżeli używasz Magento w wersji 2.3 proszę skorzystać z [pluginu dla wersji 2.3][ext9]

## Spis treści

1. [Cechy](#cechy)
1. [Wymagania](#wymagania)
1. [Instalacja](#instalacja)
1. [Konfiguracja](#konfiguracja)
    * [Parametry](#parametry)
1. [Informacje o cechach](#informacje-o-cechach)
    * [Kolejność metod płatności](#kolejność-metod-płatności)
    * [Ponowienie płatności](#ponowienie-płatności)
    * [Zapisywanie kart](#zapisywanie-kart)

## Cechy
Moduł płatności PayU dodaje do Magento 2 opcję płatności PayU. Moduł współpracuje z Magento 2 w wersji 2.4

Możliwe są następujące operacje:
  * Utworzenie płatności w sytemie PayU
  * Automatyczne odbieranie powiadomień i zmianę statusów zamówienia
  * Odebranie lub odrzucenie płatności (w przypadku wyłączonego autoodbioru)
  * Wyświetlenie metod płatności i wybranie metody na stronie podsumowania zamówienia
  * Płatność kartą bezpośrednio na stronie podsumowania zamówienia
  * Zapisanie karty i płatność zapisaną kartą
  * Ponowienie płatności
  * Utworzenie zwrotu online (pełnego lub częściowego)
  * Promowanie płatności kredytowych wykorzystując [widget kredytowy](#widget-kredytowy) w różnych podstronach sklepu (np. na stronie produktu, w koszyku)

Moduł dodaje następujące metody płatności:
  * **Płatność PayU** - wybór metody płatności i przekierowanie do banku lub formatkę kartową
  * **Płatność kartą** - wpisanie numeru karty bezpośrednio na stronie sklepu i płatność kartą
  * **PayU Raty** - płatności ratalne z przekierowaniem do formatki ratalnej PayU
  * **PayU Klarna** - odroczone płatności Klarna z przekierowaniem do formatki Klarna w PayU
  * **PayU PayPo** - odroczone płatności PayPo z przekierowaniem do formatki PayPo w PayU
  * **PayU PragmaPay** - odroczone płatności PragmaPay z przekierowaniem do formatki PragmaPay w PayU
  * **PayU Twisto** - odroczone płatności Twisto z przekierowaniem do formatki Twisto w PayU
  * **PayU Twisto podziel na 3** - odroczone płatności Twisto podziel na 3 z przekierowaniem do formatki Twisto podziel na 3 w PayU

![methods][img0]

## Wymagania

**Ważne:** Moduł ta działa tylko z punktem płatności typu `REST API` (Checkout), jeżeli nie posiadasz jeszcze konta w systemie PayU [**zarejestruj się w systemie produkcyjnym**][ext1] lub [**zarejestruj się w systemie sandbox**][ext5]

* Wersja PHP zgodna z wymaganiami zainstalowanej wersji Magento 2
* Rozszerzenia PHP: [cURL][ext2] i [hash][ext3].

## Instalacja

#### Przy użyciu Composer
`composer require payu/magento24-payment-gateway`

#### Kopiując pliki na serwer
1. Pobierz najnowszą wersję moduł z [repozytorium GitHub][ext4]
1. Rozpakuj pobrany plik
1. Połącz się z serwerem ftp i skopiuj rozpakowaną zawartość do katalogu `app/code/PayU/PaymentGateway` swojego sklepu Magento 2. Jeżeli nie ma takiego katalogu utwórz go.

Po instalacji przy użyciu Composer lub kopiując pliki z poziomu konsoli uruchom:
   * php bin/magento module:enable PayU_PaymentGateway
   * php bin/magento setup:upgrade
   * php bin/magento setup:di:compile
   * php bin/magento setup:static-content:deploy

## Konfiguracja

1. Przejdź do strony administracyjnej swojego sklepu Magento 2 [http://adres-sklepu/admin_xxx].
1. Przejdź do  **Stores** > **Configuration**.
1. Na stronie **Configuration** w menu po lewej stronie w sekcji **Sales** wybierz **Payment Methods**.
1. Na liście dostępnych metod płatności należy wybrać właściwą sekcję z listy metod **PayU** w celu konfiguracji parametrów wtyczki.
1. Po zmanie paramettrów naciśnij przycisk `Save config`.

### Parametry API

| Parameter              | Opis                                                                                                                                    |
|------------------------|-----------------------------------------------------------------------------------------------------------------------------------------|
| Tryb testowy (Sandbox) | `Tak` - transakcje będą procesowane przez system Sandbox PayU. <br/> `Nie` - transakcje będą procesowane przez system produkcyjny PayU. |

#### Parametry punktu płatności (POS)

| Parameter | Opis |
|---------|-----------|
| Id punktu płatności| Identyfikator POS-a z systemu PayU |
| Drugi klucz MD5 | Drugi klucz MD5 z systemu PayU |
| OAuth - client_id | client_id dla protokołu OAuth z systemu PayU |
| OAuth - client_secret | client_secret for OAuth z systemu PayU |

#### Parametry punktu płatności (POS) - Tryb testowy (Sandbox)
Dostępne gdy parametr `Tryb testowy (Sandbox)` jest ustawiony na `Tak`.

| Parameter | Opis |
|---------|-----------|
| Id punktu płatności| Identyfikator POS-a z systemu PayU |
| Drugi klucz MD5 | Drugi klucz MD5 z systemu PayU |
| OAuth - client_id | client_id dla protokołu OAuth z systemu PayU |
| OAuth - client_secret | client_secret for OAuth z systemu PayU |

### Parametry wtyczki "PayU - widget kredytowy"

| Parameter                                                                       | Opis                                                                                                                                                                                                                                                                                    |
|---------------------------------------------------------------------------------|-----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| Wyświetl widget kredytowy przy produktach                                       | Wartość `Tak`\|`Nie`. Wyświetla widget na stronach produktu                                                                                                                                                                                                                             |
| Wyświetl widget kredytowy w katalogu produktów                                  | Wartość `Tak`\|`Nie`. Wyświetla widget na stronach z listą produktów (np. kategorie)                                                                                                                                                                                                    |
| Wyświetl widget kredytowy w widgetach katalogu produktu np. bestseller, nowości | Wartość `Tak`\|`Nie`. Wyświetla widget na stronach z widgetami listami produktów (np. bestseller, nowości)<br>**Funkcja eksperymentalna**                                                                                                                                               |
| Wyświetl widget kredytowy w koszyku                                             | Wartość `Tak`\|`Nie`. Wyświetla widget na stronie koszyka                                                                                                                                                                                                                               |
| Wyświetl widget kredytowy w mini koszyku                                        | Wartość `Tak`\|`Nie`. Wyświetla widget na rozwijanej liście podsumowania koszyka                                                                                                                                                                                                        |
| Wyświetl widget kredytowy w podsumowaniu koszyka                                | Wartość `Tak`\|`Nie`. Wyświetla widget na stronie podsumowania koszyka z wyborem metod płatności                                                                                                                                                                                        |
| Wyklucz metody płatności kredytowych z widgetu                                  | Lista oddzielona przecinkami z [metodami płatności](https://developers.payu.com/europe/pl/docs/get-started/integration-overview/references/#installments-and-pay-later), które mają zostać pominięte w trakcie prezentacji widgetu. <br> **Rekomenduje się pozostawienie pustej listy** |

### Parametry płatności

| Parameter                           | Opis                                                                                             |
|-------------------------------------|--------------------------------------------------------------------------------------------------|
| Czy włączyć wtyczkę?                | Określa czy metoda płatności będzie dostępna w sklepie na liście płatności.                      |
| Kolejność metod płatności           | Określa kolejnośc wyświetlanych metod płatności [więcej informacji](#kolejność-metod-płatności). |
| Czy uaktywnić ponowienie płatności? | [więcej informacji](#ponowienie-płatności)                                                       |
| Pozycja na liście                   | Pozycja metody płatności na liście metod płatności                                               |

### Parametry płatności "PayU - Karty"

| Parameter                           | Opis                                                                        |
|-------------------------------------|-----------------------------------------------------------------------------|
| Czy włączyć wtyczkę?                | Określa czy metoda płatności będzie dostępna w sklepie na liście płatności. |
| Czy uaktywnić zapisywanie kart?     | [więcej informacji](#zapisywanie-kart)                                      |
| Czy uaktywnić ponowienie płatności? | [więcej informacji](#ponowienie-płatności)                                  |
| Pozycja na liście                   | Pozycja metody płatności na liście metod płatności                          |

### Parametry płatności "PayU - Raty",  "PayU - Klarna", "PayU - PayPo", "PayU - PragmaPay", "PayU - Twisto", "PayU - Twisto podziel na 3"

| Parameter                           | Opis                                                                        |
|-------------------------------------|-----------------------------------------------------------------------------|
| Czy włączyć wtyczkę?                | Określa czy metoda płatności będzie dostępna w sklepie na liście płatności. |
| Czy uaktywnić ponowienie płatności? | [więcej informacji](#ponowienie-płatności)                                  |
| Pozycja na liście                   | Pozycja metody płatności na liście metod płatności                          |

## Informacje o cechach

### Kolejność metod płatności
W celu ustalenia kolejności wyświetlanych ikon matod płatności należy podać symbole metod płatności oddzielając je przecinkiem. [Lista metod płatności][ext6].

### Ponowienie płatności
Aby użyć tej opcji, należy również odpowiednio skonfigurować POSa w PayU i wyłączyć automatycznie odbieranie płatności (domyślnie auto-odbiór jest włączony).
W tym celu należy zalogować się do panelu PayU, wejść do zakładki "Płatności elektroniczne", następnie wybrać "Moje sklepy" i punkt płatności na danym sklepie.
Opcja "Automatyczny odbiór płatności" znajduje się na samym dole, pod listą metod płatności.

Ponowienie płatności umożliwia zakładanie wielu płatności w PayU do jednego zamówienia w Magento. Wtyczka automatycznie odbierze pierwszą udaną płatność, a pozostałe zostaną anulowane.
Ponowienie płatności z punktu widzenia kupującego jest możliwe poprzez listę zamówień w Magento (pojawi się tam link "Zapłać ponownie").
Kupujący automatycznie otrzyma również wiadomość e-mail z takim linkiem.
Tym samym kupujący otrzymuje możliwość skutecznego opłacenia zamówienia, nawet jeśli pierwsza płatność była nieudana (np. brak środków na karcie, problemy z logowaniem do banku itp.).

### Zapisywanie kart
Zapisywanie kart pozwala zalogowanym użytkownikom zapamiętać kartę na poczet przyszłych płatności.
Każda zapisana karta jest "tokenizowana", przy czym Magento w żaden sposób nie przetwarza pełnych danych karty (podawane są one na wlanym widgecie hostowanym przez PayU),
ani nie zapisuje w swojej bazie tokenów kartowych (przed użyciem, aktualne tokeny dla danego użytkownika są zawsze pobierane z PayU).

W celu prawidłowego działania usługi konieczna jest dodatkowa konfiguracja w PayU, polegająca na umożliwieniu tworzenia i pobierania tokenów.
Dodatkowo, można również ustalić zasady uwierzytelniania płatności zapisaną kartą (domyślnie każda płatność zapisaną karta wymaga podania kodu CVV i
uwierzytelnieniu przez 3DS, ale można np. ustalić próg kwoty transakcji dla jakiego nie będzie to konieczne).

Kupujący może zapisać kartę podczas płatności, korzystając z opcji "Użyj i zapisz" na widgecie PayU podczas podawania danych karty.
Każda zapisywana karta podlega silnemu uwierzytelnieniu przy pierwszej płatności (CVV i 3DS).
Zapisana karta będzie pokazywać się po wybraniu płatności kartą przez PayU za zamówienie i jest widoczna w koncie użytkownika
(zakładka "Moje zapisane karty"), gdzie jest również dostępna opcja jej usunięcia. 

### Widget kredytowy

W celu poinformowania klienta o możliwościach płatności kredytowej dla konkretnego produktu, zalecamy umieszczenie widgetu kredytowego przy produktach w listach produktów, opisie (szczegółach) wybranego produktu, koszyku i przy finalizacji zamówienia (przed płatnością).
Parametry konfiguracji opisane w sekcji [Parametry wtyczki "PayU - widget kredytowy"](#parametry-wtyczki-payu-widget-kredytowy) pozwalają na elastyczne zarządzanie miejscami wyświetlania widgetu kredytowego.

Przykładowa prezentacja widgetu kredytowego

![widget][img1]

<!--external links:-->
[ext0]: https://github.com/PayU-EMEA/plugin_magento
[ext1]: https://www.payu.pl/oferta-handlowa
[ext2]: http://php.net/manual/en/book.curl.php
[ext3]: http://php.net/manual/en/book.hash.php
[ext4]: https://github.com/PayU-EMEA/plugin_magento_24/releases/latest
[ext5]: https://secure.snd.payu.com/boarding/?pk_campaign=Plugin-Github&pk_kwd=Magento2#/form
[ext6]: http://developers.payu.com/pl/overview.html#paymethods
[ext7]: https://github.com/PayU-EMEA/plugin_magento_2
[ext8]: README.EN.md
[ext9]: https://github.com/PayU-EMEA/plugin_magento_23
[ext10]: CHANGELOG.md

<!--images:-->
[img0]: readme_images/methods_pl.png
[img1]: readme_images/widget_pl.png
