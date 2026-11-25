# DoneProject Plugin

Lühikirjeldus

DoneProject on kerge WordPressi plugin-skelet, mis võimaldab kuvada antud kategoorias olevaid lehti või postitusi shortcode'iga koos esimesest postituse sisust eraldatud pildist (thumbnail). See lahendus ei kasuta featured image’t, vaid otsib esimest <img> post_content sees ja genereerib vajadusel pisipildi (wp-content/uploads/doneproject-thumbs/).

Peamised funktsioonid (praegune seis)

- Shortcode: [doneproject_pages] (alias [doneproject_list]) kuvab kategooria kirjeid koos pealkirjade ja sisust võetud pisipiltidega.
- Thumb-generation: kui pilt asub lokalsetes uploads kaustas, genereeritakse serveris kärbitud pisipilt (nimetatud uploads/do neproject-thumbs/ kataloogi).
- Valikulised atribuudid: category (slug või ID), post_type (page/post), count, image_width, image_height, show_excerpt.
- PSR-4 struktuur: plugin on üles ehitatud klassipõhiselt (src/), lihtsa autoload’iga ja composer.json võimalusega.

Näide shortcode’ist

- Kuvab kuni 6 lehte kategooria slugiga "valminud-objektid" pisipildiga 180×120:

  [doneproject_pages category="valminud-objektid" post_type="page" count="6" image_width="180" image_height="120"]

- Näita ka kokkuvõtet:

  [doneproject_pages category="valminud-objektid" post_type="post" count="10" show_excerpt="1"]

Nõuded

- WordPress ≥ 5.6 (testitud kuni 6.x)
- PHP ≥ 7.4
- WP Image Editor (GD või Imagick) pilditöötluseks

Paigaldus

1. Klooni või laadi plugin kaustana repo juurest: `doneproject-plugin` → `wp-content/plugins/doneproject-plugin`
2. Aktiveeri WordPressi adminis (Plugins → DoneProject Plugin).
3. Lisa lehele/ postitusele shortcode (näide eespool).

Testid

Unit-testid kasutavad PHPUnitit ja WordPressi funktsioonide asendajaid (`tests/wp-stubs.php`), seega WordPressi installatsiooni pole vaja.

1. `composer install`
2. `composer test` (või `vendor/bin/phpunit`)
3. Kaetuse aruanne: `php -d pcov.enabled=1 vendor/bin/phpunit --coverage-text` (nõuab pcov või Xdebug laiendust)

Piirangud ja tähelepanekud

- Pages ja category: vaikimisi ei kasuta WordPress pages taksonoomiat "category"; kui kasutad post_type="page" ja sinu lehed ei ole category-ga seotud, shortcode ei tagasta tulemusi — kasuta post tüüpi või registreeri category ka lehtedele.
- Kui <img> pole sisu sees või pilt on välisel serveril, praegune versioon kas ei kuva pilti või kasutab otse välis-URL’i (ei sideloada remote pilte). Sideloading ja featured-image fallback pole praegu vaikimisi sees, kuid neid saab lisada.
- uninstall.php ei eemalda automaatselt genereeritud thumbnails kataloogi; võin lisada puhastuse, kui soovid.

Edaspidine arendus (soovitused)

- Lisada featured-image fallback, kui contentist pilti ei leita.
- Sideloader: automaatne remote image download ja thumbnail-generatsioon.
- Admin-seaded pisipiltide vaikimõõtude ja thumb-kausta kontrolliks.
- WP-CLI käsk thumbnailide uuesti genereerimiseks ja uninstall puhastuse lisamiseks.

Autor ja litsents

Autor: liikane
Litsents: GPLv3 (kasutab repositooriumi olemasolevat litsentsi)
