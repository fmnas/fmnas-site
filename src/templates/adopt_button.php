<?php
function adopt_button() { ?>
    <section class="adopt">
        <form id="adopt" action="/application" method="POST">
            <h2>Adopt a Pet</h2>
            <a href="/application">
                <button id="adopt_button" type="submit">Apply Online Now</button>
            </a>
        </form>
    </section>
<?php }
