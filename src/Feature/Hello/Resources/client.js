/**
 * Hello feature client module — demonstrates the nqphp runtime.
 *
 * Loaded with:
 *   <script type="module" src="/_nqphp/js/Hello/client.js"></script>
 *
 * Posts to /json (a state-changing override of the GET route isn't
 * wired today, so this is a demo of the wiring rather than a real
 * feature flow — the next slice will add a POSTable endpoint).
 */

import { csrf, fetchJson } from '/_nqphp/js/nqphp-runtime.js';

const out = document.querySelector('#hello-out');
const button = document.querySelector('#hello-ping');

if (button && out) {
  button.addEventListener('click', async (event) => {
    event.preventDefault();
    out.textContent = `csrf() = ${csrf() ?? '(no token yet)'}`;
    try {
      const data = await fetchJson('/json');
      out.textContent += ` | GET /json → ${JSON.stringify(data)}`;
    } catch (err) {
      out.textContent += ` | error: ${err.message}`;
    }
  });
}
