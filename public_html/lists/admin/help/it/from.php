<p>Puoi usare tre metodi per impostare la linea del mittente (da):</p>
<ul>
<li>Una parola: sar&agrave; riformattato come &lt;la parola&gt;@<?php echo $domain?>
<br>Per esempio: <b>info</b> diventer&agrave; <b>info@<?php echo $domain?></b>
<br>In molti programmi email il campo "da" verr&agrave; visualizzato come <b>info@<?php echo $domain?></b></li>
<li>Due o pi&ugrave; parole: sar&agrave; riformattato come <i>le parole che hai scritto</i> &lt;listmaster@<?php echo $domain?>&gt;
<br>Per esempio: <b>info news</b> diventer&agrave; <b>info news &lt;listmaster@<?php echo $domain?>&gt;</b>
<br>In molti programmi email il campo "da" verr&agrave; visualizzato come <b>info news</b></li>
<li>Nessuna o pi&ugrave; parole e un indirizzo email: sar&agrave; riformattato come <i>Parole</i> &lt;indirizzoemail&gt;
<br>Per esempio: <b>Mio nome mio@email.it</b> diventer&agrave; <b>Mio nome &lt;mio@email.it&gt;</b>
<br>In molti programmi email il campo "da" verr&agrave; visualizzato come <b>Mio nome</b></li>
</ul>