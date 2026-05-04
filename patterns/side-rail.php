<?php
/**
 * Title: Side Rail Navigation
 * Slug: dariobimbi/side-rail
 * Categories: dariobimbi
 * Description: Fixed-position quick-jump nav showing the current section as you scroll. Hidden under 1180px so it never overlaps constrained content. Marked up as a real <nav> landmark with aria-label and aria-current for screen readers.
 */
?>
<!-- wp:html -->
<nav class="side-rail" aria-label="Page sections">
    <ol class="side-rail-list">
        <li><a href="#about"><span>About</span></a></li>
        <li><a href="#experience"><span>Experience</span></a></li>
        <li><a href="#projects"><span>Work</span></a></li>
        <li><a href="#skills"><span>Skills</span></a></li>
        <li><a href="#contact"><span>Contact</span></a></li>
    </ol>
</nav>
<!-- /wp:html -->
