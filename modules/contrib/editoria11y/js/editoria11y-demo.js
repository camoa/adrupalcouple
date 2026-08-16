/* globals Drupal, drupalSettings, Ed11y, console */
/**
 * Drupal initializer.
 * Launch as behavior and pull variables from config.
 */

Drupal.behaviors.editoria11yAdmin = {
	attach: function (context, settings) {
		'use strict';

		if (context !== document) {
			return;
		}

		const ed11yContain = document.getElementById('ed11y-demo');
		//ed11yContain.setAttribute('contenteditable', '');
		const main = document.querySelector('main');
		if (main && !main.querySelector('#ed11y-demo')) {
			main.appendChild(ed11yContain);
		} else if (!main) {
			let newMain = document.createElement('main');
			ed11yContain.insertAdjacentElement('beforebegin', newMain);
			newMain.insertAdjacentElement('afterbegin', ed11yContain);
		}

		let iframePlaceholder = `
    <!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>example</title></head>
    <body><div style="position: absolute; top:0; bottom: 0; left:0; right:0; border: 2px #444; background: #eee; display: flex; align-items: center; justify-content: center; font-size: 10vw; font-family: monospace;">&lt; iframe &gt;</div>
    </body></html>
    `;

		const iframeBlob = new Blob([iframePlaceholder], { type: 'text/html' });

		// 3. Generate a blob URL from the Blob object
		const iSrc = URL.createObjectURL(iframeBlob);

		ed11yContain.innerHTML = `
		<div id="editoria11y-demo" style="font-size: 2rem; font-family: serif;">Editoria11y: All Tests</div>

		<section id="headings">

            <h2 id="heading-tests">Headings</h2>

			<p>This heading skipped a level:</p>
            <div class="positive"><h6 id="this-h6-should-have-been-an-h3">Oops</h6></div>

            <p>This heading has no text at all:</p>
			<div class="positive"><h4 class="empty-example"> </h4>
            </div>

			<p>This short blockquote might be heading:</p>
            <div class="positive"><blockquote>I'm a fake heading</blockquote></div>

			<p>This all-bold paragraph might be a heading:</p>
            <p class="positive"><strong>I am a fake heading</strong></p>

			<p>This heading is really long:</p>
			<div class="positive"><h4>Headings and subheadings create a navigable table of contents for assistive devices. The numbers indicate indents in a nesting relationship. Very long headings probably mean the author is saying too much in the heading.</h4></div>

        </section>


        <section>
            <h2 id="text-alternatives">Text alternatives for images</h2>

			<p>This heading is made of an image of text with no alt:</p>
			<div class="positive">
				<h4><img alt="" src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 240 120'%3E%3Crect width='240' height='120' fill='%23cccccc'%3E%3C/rect%3E%3Ctext x='50%25' y='50%25' dominant-baseline='middle' text-anchor='middle' font-family='monospace' font-size='26px' fill='%23333333'%3EImage%3C/text%3E%3C/svg%3E" ></h4>
			</div>

			<p>Image with no alt attribute at all:</p>
            <p class="positive"><img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 240 120'%3E%3Crect width='240' height='120' fill='%23cccccc'%3E%3C/rect%3E%3Ctext x='50%25' y='50%25' dominant-baseline='middle' text-anchor='middle' font-family='monospace' font-size='26px' fill='%23333333'%3EImage%3C/text%3E%3C/svg%3E"></p>

            <p>Image with an invalid alt attribute, e.g., <code class="language-plaintext highlighter-rouge">alt="'"</code></p>

            <p class="positive"><img alt="'" src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 240 120'%3E%3Crect width='240' height='120' fill='%23cccccc'%3E%3C/rect%3E%3Ctext x='50%25' y='50%25' dominant-baseline='middle' text-anchor='middle' font-family='monospace' font-size='26px' fill='%23333333'%3EImage%3C/text%3E%3C/svg%3E"></p>

            <p id="image-with-a-filename-as-an-alt">Image with a filename as an alt</p>

            <p class="positive"><img alt="filename.jpg" src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 240 120'%3E%3Crect width='240' height='120' fill='%23cccccc'%3E%3C/rect%3E%3Ctext x='50%25' y='50%25' dominant-baseline='middle' text-anchor='middle' font-family='monospace' font-size='26px' fill='%23333333'%3EImage%3C/text%3E%3C/svg%3E"></p>

            <p id="image-with-redundant-text-in-its-alt">Image with redundant text in its alt, such as “image of:”</p>

            <p class="positive"><img alt="Image of a turtledove." src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 240 120'%3E%3Crect width='240' height='120' fill='%23cccccc'%3E%3C/rect%3E%3Ctext x='50%25' y='50%25' dominant-baseline='middle' text-anchor='middle' font-family='monospace' font-size='26px' fill='%23333333'%3EImage%3C/text%3E%3C/svg%3E"></p>

			<p id="linked-image-with-redundant-text-in-its-alt">Linked image with redundant text in its alt, such as “image of:”</p>

			<p class="positive"><a href="./editable.htm"><img alt="Image of a turtledove." src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 240 120'%3E%3Crect width='240' height='120' fill='%23cccccc'%3E%3C/rect%3E%3Ctext x='50%25' y='50%25' dominant-baseline='middle' text-anchor='middle' font-family='monospace' font-size='26px' fill='%23333333'%3EImage%3C/text%3E%3C/svg%3E"></a></p>

            <p id="image-with-placeholder-alt">Image with placeholder alt:</p>
            <p class="positive"><img alt="TBD" src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 240 120'%3E%3Crect width='240' height='120' fill='%23cccccc'%3E%3C/rect%3E%3Ctext x='50%25' y='50%25' dominant-baseline='middle' text-anchor='middle' font-family='monospace' font-size='26px' fill='%23333333'%3EImage%3C/text%3E%3C/svg%3E"></p>

			<p id="linked-image-with-placeholder-alt"><strong>Linked</strong> image with placeholder alt:</p>
            <p class="positive"><a href="./editable.htm"><img alt="TBD" src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 240 120'%3E%3Crect width='240' height='120' fill='%23cccccc'%3E%3C/rect%3E%3Ctext x='50%25' y='50%25' dominant-baseline='middle' text-anchor='middle' font-family='monospace' font-size='26px' fill='%23333333'%3EImage%3C/text%3E%3C/svg%3E"></a></p>

			<p id="image-with-machine-alt">Image with machine code alt:</p>
			<p class="positive"><img alt="$700VIDEOSABOUTTURTLESALL2SECONDSLONG" src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 240 120'%3E%3Crect width='240' height='120' fill='%23cccccc'%3E%3C/rect%3E%3Ctext x='50%25' y='50%25' dominant-baseline='middle' text-anchor='middle' font-family='monospace' font-size='26px' fill='%23333333'%3EImage%3C/text%3E%3C/svg%3E"></p>

			<p id="linked-image-with-machine-alt">Linked image with machine code alt:</p>
			<p class="positive"><a href="./editable.htm"><img alt="$700VIDEOSABOUTTURTLESALL2SECONDSLONG" src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 240 120'%3E%3Crect width='240' height='120' fill='%23cccccc'%3E%3C/rect%3E%3Ctext x='50%25' y='50%25' dominant-baseline='middle' text-anchor='middle' font-family='monospace' font-size='26px' fill='%23333333'%3EImage%3C/text%3E%3C/svg%3E"></a></p>

            <p id="image-with-very-long-alt-text">Image with very long alt text:</p>

            <p class="positive"><img alt="This description should be brief. Screen readers cannot jump from sentence to sentence in alt text, so listeners just hear one monster pile of text and if they miss something they have to start over. This, for example, is really quite awfully long and should be much shorter." src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 240 120'%3E%3Crect width='240' height='120' fill='%23cccccc'%3E%3C/rect%3E%3Ctext x='50%25' y='50%25' dominant-baseline='middle' text-anchor='middle' font-family='monospace' font-size='26px' fill='%23333333'%3EImage%3C/text%3E%3C/svg%3E"></p>

            <p id="images-in-links-where-the-alt-text-may-be-describing-the-image-instead-of-the-link">Images in links where the alt text may be describing the image instead of the link</p>

            <p class="positive"><a  href="https://www.youtube.com/watch?v=DLzxrzFCyOs">This link has text and an image.<img alt="A lovely gray box" src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 240 120'%3E%3Crect width='240' height='120' fill='%23cccccc'%3E%3C/rect%3E%3Ctext x='50%25' y='50%25' dominant-baseline='middle' text-anchor='middle' font-family='monospace' font-size='26px' fill='%23333333'%3EImage%3C/text%3E%3C/svg%3E"></a></p>

			<p id="images-in-links-decorative">Any linked image (test off by default)</p>
			<p class="positive"><a href="https://www.youtube.com/watch?v=DLzxrzFCyOs"><img alt="Hello there" src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 240 120'%3E%3Crect width='240' height='120' fill='%23cccccc'%3E%3C/rect%3E%3Ctext x='50%25' y='50%25' dominant-baseline='middle' text-anchor='middle' font-family='monospace' font-size='26px' fill='%23333333'%3EImage%3C/text%3E%3C/svg%3E"></a></p>

			<p>Image in figure marked decorative:</p>
			<figure class="positive">
				<img alt="" src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 240 120'%3E%3Crect width='240' height='120' fill='%23cccccc'%3E%3C/rect%3E%3Ctext x='50%25' y='50%25' dominant-baseline='middle' text-anchor='middle' font-family='monospace' font-size='26px' fill='%23333333'%3EImage%3C/text%3E%3C/svg%3E">
				<figcaption>Figcaption is present.</figcaption>
			</figure>

			<p>Image in figure with an alt the same as the figcaption:</p>
            <figure class="positive">
                <img alt="Figcaption is the same as alt" src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 240 120'%3E%3Crect width='240' height='120' fill='%23cccccc'%3E%3C/rect%3E%3Ctext x='50%25' y='50%25' dominant-baseline='middle' text-anchor='middle' font-family='monospace' font-size='26px' fill='%23333333'%3EImage%3C/text%3E%3C/svg%3E">
                <figcaption>Figcaption is the same as alt</figcaption>
            </figure>
			<p>Image in a carousel without an alt:</p>
			<div class="positive">
				<img alt="" class="carousel" src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 240 120'%3E%3Crect width='240' height='120' fill='%23cccccc'%3E%3C/rect%3E%3Ctext x='50%25' y='50%25' dominant-baseline='middle' text-anchor='middle' font-family='monospace' font-size='26px' fill='%23333333'%3EImage%3C/text%3E%3C/svg%3E">
			</div>
        </section>
		<section>
			<h2>Contrast</h2>
			<p>Illegible color combination:</p>
			<div class="positive"><p style="background: #ccc; color: #ddd;">Super legible.</p></div>
			<p>Color needs a manual check:</p>
			<div class="positive"><p style="background: linear-gradient(45deg,#ccc 25%,#0000 25% 75%,#ccc 75%,#ccc),linear-gradient(45deg,#ccc 25%,#0000 25% 75%,#ccc 75%,#ccc)">And incalculable.</p></div>
			<div class="positive"><label>Input with low contrast
				<input type="text" style="color: #777;"></label></div>

			<style>
				#unsupported-placeholder::placeholder {
					color: color(rec2020 0.9 0.4 0.2);
				}
			</style>

			<label for="unsupported-placeholder">Color of placeholder cannot be calculated</label>
			<div class="positive" style="background: transparent;"><input type="text" id="unsupported-placeholder" placeholder="Rec2020 color space"></div>


			<p>SVG graphic without enough contrast</p>
			<div class="positive"><svg style="width: 16px; height: 16px;" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--!Font Awesome Free v7.1.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2026 Fonticons, Inc.--><path fill="#999" d="M416 208c0 45.9-14.9 88.3-40 122.7L502.6 457.4c12.5 12.5 12.5 32.8 0 45.3s-32.8 12.5-45.3 0L330.7 376C296.3 401.1 253.9 416 208 416 93.1 416 0 322.9 0 208S93.1 0 208 0 416 93.1 416 208zM208 352a144 144 0 1 0 0-288 144 144 0 1 0 0 288z"/></svg></div>

			<p>SVG graphic where contrast needs a manual check</p>
			<div class="positive" style="background: linear-gradient(45deg,#ccc 45%,#0000 45% 75%,#ccc 75%,#ccc),linear-gradient(145deg,#ccc 45%,#0000 45% 75%,#ccc 75%,#ccc)"><svg style="width: 16px; height: 16px;" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--!Font Awesome Free v7.1.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2026 Fonticons, Inc.--><path fill="color(rec2020 0.9 0.4 0.2)" d="M416 208c0 45.9-14.9 88.3-40 122.7L502.6 457.4c12.5 12.5 12.5 32.8 0 45.3s-32.8 12.5-45.3 0L330.7 376C296.3 401.1 253.9 416 208 416 93.1 416 0 322.9 0 208S93.1 0 208 0 416 93.1 416 208zM208 352a144 144 0 1 0 0-288 144 144 0 1 0 0 288z"/></svg></div>


		</section>
        <h3 id="embeds">Embeds &amp; Media</h3>

		<p>Video flagged as manual check needed for captioning:</p>
		<div class="positive" style="width: 560px; aspect-ratio: 560 / 315; max-width: 100%; cursor: not-allowed;">
        <iframe width="560" height="315" inert="" src="https://www.youtube-nocookie.com/embed/QGsevnbItdU?si=W_b2i2o_n5v1ArvA" style="width: 560px; aspect-ratio: 560 / 315; max-width: 100%; cursor: not-allowed;" title="YouTube: How it's done video. Playback is disabled." sandbox="allow-scripts allow-same-origin" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" referrerpolicy="origin" allowfullscreen></iframe>
        <div><small><em>Playback disabled to prevent cookies.</em></small></div>
		</div>
		
		<p>Audio needs a transcript:</p>
		<div class="positive">
			<audio controls="" style="height: 60px;"></audio>
		</div>

		<p>Iframe missing a title that also needs a manual check:</p>
		<div class="positive">
			<iframe src=${iSrc}></iframe>
		</div>

		<p>Unfocusable iframe:</p>
		<div class="positive">
			<iframe title="example" sandbox="" tabindex="-1" src=${iSrc}></iframe>
		</div>

		<p>Nested interactive components</p>
		<div class="positive">
			<details><summary>Accordion</summary>
				<details><summary>Accordion</summary>Surprise!</details>
			</details>
		</div>

        <h2 id="meaningful-links">Meaningful Links</h2>

        <p id="links-with-no-text-at-all">Links with no text at all</p>
        <p class="positive"><a href="https://www.youtube.com/watch?v=DLzxrzFCyOs"
		style="display: inline-block; width: 3rem; border-bottom: 1px solid;"></a> Empty links</p>

		<p id="links-titled-with-a-url">Links only labelled with a URL:</p>
        <p class="positive"><a  href="https://www.youtube.com/watch?v=DLzxrzFCyOs">https://www.youtube.com/watch?v=DLzxrzFCyOs</a></p>

		<p id="links-titled-with-a-symbol">Links only labelled with symbols:</p>
		<p class="positive"><a href="https://www.youtube.com/watch?v=DLzxrzFCyOs">↣</a></p>

		<p>Links with the same names go different places</p>
		<p class="positive"><a href="https://www.youtube.com/watch?v=fjOeJssZX_Q">Turtles</a> <a href="https://www.youtube.com/watch?v=cWppAbqm9I8">Turtles</a></p>

		<p id="links-titled-with-doi">Links only labelled with a DOI number:</p>
		<p class="positive"><a href="https://doi.org/10.1145/3493612.3520468">doi.org/10.1145/3493612.3520468</a></p>

		<p id="links-titled-with-click">Links with meaningless labels:</p>
		<p class="positive"><a href="https://www.youtube.com/watch?v=DLzxrzFCyOs" title="https://www.youtube.com/watch?v=DLzxrzFCyOs">Click here</a></p>

		<p id="links-titled-with-empty">Linked icons without text alternatives:</p>
		<p class="positive"><a href="https://www.youtube.com/watch?v=DLzxrzFCyOs"><svg style="width: 16px; height: 16px;" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--!Font Awesome Free v7.1.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2026 Fonticons, Inc.--><path d="M416 208c0 45.9-14.9 88.3-40 122.7L502.6 457.4c12.5 12.5 12.5 32.8 0 45.3s-32.8 12.5-45.3 0L330.7 376C296.3 401.1 253.9 416 208 416 93.1 416 0 322.9 0 208S93.1 0 208 0 416 93.1 416 208zM208 352a144 144 0 1 0 0-288 144 144 0 1 0 0 288z"/></svg></a></p>

        <p id="links-that-open-in-a-new-window-without-an-external-link-icon-or-text-warning">Links that open in a new window without an external link icon or text warning</h3>

        <p class="positive"><a href="https://www.youtube.com/watch?v=DLzxrzFCyOs" target="_blank">Alert for opens-in test.</a></p>

        <p id="links-to-PDF">Links to PDFs</p>
        <p class="positive"><a href="https://www.youtube.com/watch?v=dQw4w9WgXcQ#.pdf" class="">Have fun.</a></p>

		<p id="links-to-doc">Links to other documents</p>
		<p class="positive"><a href="https://www.youtube.com/watch?v=dQw4w9WgXcQ#.doc" class="">A fake doc link.</a></p>

		<p id="links-redundant">Links with redundant title attributes</p>
		<p class="positive"><a href="https://www.youtube.com/watch?v=dQw4w9WgXcQ" title="Hover me">Hover me</a></p>

        <p>Links with attributes that remove them from the tab index:</p>
        <p class="positive"><a href="./editable.htm" aria-hidden="true">Aria-hidden links without a negative tabindex.</a></p>

		<p>Links aria-labelledby attributes to elements with no text:</p>
		<p class="positive"><a href="./editable.htm" id="oops" aria-labelledby="oops"></a></p>

		<p>Links with generic words in their aria label:</p>
		<p class="positive"><a href="www.google.com/" aria-label="has aria label">learn more</a></p>

		<p>Linked image with no alt text:</p>
		<p class="positive"><a href="./editable.htm"><img alt="" src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 240 120'%3E%3Crect width='240' height='120' fill='%23cccccc'%3E%3C/rect%3E%3Ctext x='50%25' y='50%25' dominant-baseline='middle' text-anchor='middle' font-family='monospace' font-size='26px' fill='%23333333'%3EImage%3C/text%3E%3C/svg%3E" class=""></a></p>

		<p>Linked image with unpronounceable alt text:</p>
		<p class="positive"><a href="./editable.htm"><img alt="'" src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 240 120'%3E%3Crect width='240' height='120' fill='%23cccccc'%3E%3C/rect%3E%3Ctext x='50%25' y='50%25' dominant-baseline='middle' text-anchor='middle' font-family='monospace' font-size='26px' fill='%23333333'%3EImage%3C/text%3E%3C/svg%3E" class=""></a></p>

		<p>Linked image with no alt text attribute:</p>
		<p class="positive"><a href="./editable.htm"><img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 240 120'%3E%3Crect width='240' height='120' fill='%23cccccc'%3E%3C/rect%3E%3Ctext x='50%25' y='50%25' dominant-baseline='middle' text-anchor='middle' font-family='monospace' font-size='26px' fill='%23333333'%3EImage%3C/text%3E%3C/svg%3E" class=""></a></p>

		<p>Linked image with no alt text attribute in a link with text:</p>
		<p class="positive"><a href="./editable.htm">Search <img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 240 120'%3E%3Crect width='240' height='120' fill='%23cccccc'%3E%3C/rect%3E%3Ctext x='50%25' y='50%25' dominant-baseline='middle' text-anchor='middle' font-family='monospace' font-size='26px' fill='%23333333'%3EImage%3C/text%3E%3C/svg%3E" class=""></a></p>

		<p>Linked image with only a filename as alt text:</p>
        <p class="positive"><a href="./editable.htm"><img alt="http://jpg.gif" src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 240 120'%3E%3Crect width='240' height='120' fill='%23cccccc'%3E%3C/rect%3E%3Ctext x='50%25' y='50%25' dominant-baseline='middle' text-anchor='middle' font-family='monospace' font-size='26px' fill='%23333333'%3EImage%3C/text%3E%3C/svg%3E">
        </a></p>

		<p>Linked image with suspiciously long alt text:</p>
        <p class="positive"><a href="./editable.htm"><img alt="Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum." src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 240 120'%3E%3Crect width='240' height='120' fill='%23cccccc'%3E%3C/rect%3E%3Ctext x='50%25' y='50%25' dominant-baseline='middle' text-anchor='middle' font-family='monospace' font-size='26px' fill='%23333333'%3EImage%3C/text%3E%3C/svg%3E">
        </a></p>

		<p>Broken in page links</p>
		<p class="positive">
			<a href="#oh-no">There's no such ID.</a>
		</p>

		<p>Links to dev server</p>
		<p class="positive">
			<a href="https://tugboatqa.com">Oops</a>
		</p>

		<p>Duplicate anchors</p>
		<p class="positive"><a id="dupe" href="#dupe">Anchor 1</a> <a id="dupe" href="#dupe">Anchor 2</a></p>

        <h2 id="tables">Tables</h2>

        <h3 id="tables-without-valid-headers">Tables without valid headers</h3>

        <table class="positive"><tbody><tr><td>A table without a TH header is invalid</td><td class="positive"><h4 >Outline headers do not count</h4></td><td class="negative"><p><strong>Disable should be content header test inside tables</strong></p></td></tr></tbody></table>

        <table><thead>
        <tr>
            <th class="positive"></th>
			<th class="negative">Empty headers</th>
		</tr>
        </thead>
            <tbody>
            <tr class="negative">
                <th>I found</th>
                <td>700 Videos of Turtles</td>
            </tr>
            <tr class="negative">
                <th>Each</th>
                <td>2 Seconds Long</td>
            </tr>
            </tbody>
        </table>
        <h2 id="legibility">Legibility</h2>

		<div class="positive"><p>SEVERAL WORDS IN A ROW OF CAPS LOCK TEXT WILL TRIGGER A MANUAL CHECK WARNING.</p></div>

		<p class="positive"><u>Underlined text</u></p>

		<p class="positive"><em>A little italics is fine, but large blocks of of emphasized text are not very legible. A little is acceptable of course, but there are limits, and a whole paragraph is way too much. This, for example, is a whole paragraph. It's kind of hard to read, isn't it? But this test does not trip until it is this long, so you should not see false positives in your content unless you write really big paragraphs like this regularly.</em></p>

		<p class="positive" style="text-align: justify;">Justified text</p>

		<p class="positive">Excessive superscript or subscript; <sub>subscript should be used for small references and footnotes, not entire sentences or paragraphs.</sub></p>

		<p class="positive"><span style="font-size: 10px;">Teeny fonts</span></p>

		<div class="positive">
			<p>* Fake lists using common characters,</p><p>* letters, numbers and emoji.</p>
		</div>

		<div class="positive"><li>Missing UL tag</li></div>

		<h2>Forms</h2>

		<p>Button with no label</p>
		<div class="positive">
			<button></button>
		</div>

		<p>Inputs with an invisible label, asking if there is also a visible label.</p>
		<div class="positive">
			<input type="text" title="Email">
		</div>

		<p>Inputs with only a placeholder label, asking if there is also a visible label.</p>
		<div class="positive">
			<input type="text" placeholder="Email">
		</div>

		<div class="positive">
			<p>Button with an accessible label different from its visible label.</p>
			<button aria-label="bye">Hi</button>
		</div>

		<div class="positive">
			<p>Button with an invalid aria-labelledby attribute.</p>
			<button aria-labelledby="bye">Hi</button>
		</div>

		<div class="positive">
			<p>Button with redundant label.</p>
			<button>Press this button</button>
		</div>

		<div class="positive">
			<p>Reset button.</p>
			<input type="reset">
		</div>

		<p class="positive"><a href="./editable.htm" tabindex="2">Jumbling the tab index</a></p>

    `;

	}
};


