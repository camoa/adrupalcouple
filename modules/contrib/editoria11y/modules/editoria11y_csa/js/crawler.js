let ed11yCrawlOnce = false;

Drupal.behaviors.editoria11yAdmin = {
  attach: function (context, settings) {
    "use strict";

    if (ed11yCrawlOnce) {
      return;
    }
    ed11yCrawlOnce = true;

    const wrapper = document.querySelector('.editoria11y-crawl');

    const crawlControls = wrapper.querySelector('#crawl-controls');
    if (document.querySelector('.views-edit-view, #views-edit-form') || !crawlControls) {
      return;
    }

    const filters = wrapper.querySelector('#crawl-filters form');
    if (filters) {
      const details = document.createElement('details');
      // @todo 3.0 test and style in Gin admin theme.
      details.classList.add('claro-details', 'ed11y-filters');
      const summary = document.createElement('summary');
      summary.classList.add('claro-details__summary');
      summary.textContent = Drupal.t('Filter which pages will be crawled');
      details.appendChild(summary);
      if (document.querySelector('#edit-reset')) {
        details.open = true;
      }
      filters.insertAdjacentElement('beforebegin', details);
    }

    const urlParams = new URLSearchParams(window.location.search);
    let paused = false;


    let progress = wrapper.querySelector('progress');
    const currentPage = progress.value ?? 1;
    const maxPage = progress.max ?? 1;
    const itemsPerPage = 50;

    const progressWrap = document.createElement('div');
    progressWrap.classList.add('progress');
    const progressLabel = document.createElement('div');
    progressLabel.classList.add('progress__label');
    const progressTrack = document.createElement('div');
    progressTrack.classList.add('progress__track');
    const progressBar = document.createElement('div');
    progressBar.classList.add('progress__bar');
    const progressPercent = document.createElement('div');
    progressPercent.classList.add('progress__percentage');
    const progressDescription = document.createElement('div');
    progressDescription.classList.add('progress__description');
    progressTrack.appendChild(progressBar);
    progressWrap.append(progressLabel, progressTrack, progressPercent, progressDescription);
    crawlControls.insertAdjacentElement('afterend', progressWrap);

    const exit = document.getElementById('crawl-done');
    exit.textContent = Drupal.t('Cancel');


    const frameBucket = document.createElement('div');

    const statusBar = document.createElement('div');
    const crawlSpeed = document.createElement('select');
    crawlSpeed.name = 'crawlSpeed';
    crawlSpeed.id = 'crawlSpeed-select';
    crawlSpeed.setAttribute('aria-describedby', 'slow-warning');
    crawlSpeed.classList.add('form-select', 'form-element', 'form-element--type-select');
    const speed = urlParams.get('speed') ?? '500';
    const createOption = function (val) {
      const option = document.createElement('option');
      option.value = val;
      option.textContent = val;
      if (val === speed) {
        option.selected = 'true';
      }
      crawlSpeed.appendChild(option);
    };
    const speeds = [
      '100', '250', '500', '1000', '2500', '5000'
    ];
    speeds.forEach((speed) => createOption(speed));
    const speedBlock = document.createElement('div');
    speedBlock.classList.add('views-exposed-form__item', 'js-form-item', 'form-item', 'js-form-type-select', 'form-type--select', 'js-form-item-type', 'form-item--type');
    const speedLabel = document.createElement('label');
    speedLabel.for = 'crawlSpeed-select';
    speedLabel.classList.add('form-item__label');
    speedLabel.textContent = Drupal.t('Millisecond pause between page requests');
    speedBlock.appendChild(crawlSpeed);
    speedBlock.appendChild(speedLabel);
    statusBar.prepend(speedBlock);
    crawlControls.append(statusBar);


    const messageWrap = document.createElement('div');
    messageWrap.classList.add('crawl-message-wrap');
    crawlControls.insertAdjacentElement('afterend', messageWrap);
    const message = document.createElement('p');
    message.textContent = Drupal.t('This tab needs to remain open and in the foreground during the crawl.');
    messageWrap.appendChild(message);
    const estimate = document.createElement('p');
    const estimateLabel = document.createElement('span');
    estimateLabel.textContent = Drupal.t('Estimated time:') + ' ';
    estimate.appendChild(estimateLabel);
    const speedVal = document.createElement('span');
    estimate.appendChild(speedVal);
    messageWrap.appendChild(estimate);

    let pageLoad = 100;
    const observer = new PerformanceObserver((list) => {
      list.getEntries().forEach((entry) => {
        if (entry.entryType === 'navigation' && entry.duration > 100) {
          pageLoad = entry.duration;
          estimateTime();
        }
      });
    });
    observer.observe({ type: 'navigation', buffered: true });

    const estimateTime = function () {
      // 50 items per page, times speed, plus ~3 seconds lag.
      let desiredSpeed = parseInt(crawlSpeed.value);
      const likelySpeed = desiredSpeed < pageLoad ? (pageLoad * 2 + desiredSpeed) / 3 : desiredSpeed;
      const seconds = ((maxPage * itemsPerPage * likelySpeed) - ((currentPage - 1) * itemsPerPage * likelySpeed) + (maxPage * 10000)) / 1000;
      let speed;
      if (seconds > 120 * 60) {
        speed = Math.ceil(seconds / 3600) + ' ' + Drupal.t('hours');
      } else if (seconds > 60) {
        speed = Math.ceil(seconds / 60) + ' ' + Drupal.t('minutes');
      } else {
        speed = '1 ' + Drupal.t('minute');
      }
      speedVal.textContent = speed;
      if (seconds > 1200) {
        speedVal.classList.add('long-crawl');
      } else {
        speedVal.classList.remove('long-crawl');
      }
    };
    estimateTime();
    crawlSpeed.addEventListener('change', () => {
      estimateTime();
    });


    let urls = [];
    const rows = Array.from(document.querySelectorAll('.editoria11y-crawl .crawl-url a'));
    rows.forEach((link) => {
      urls.push(link.href);
    });
    const maxRows = urls.length;
    const totalMax = (maxPage - 1) * itemsPerPage + maxRows;
    window.ed11ySynced = 0;

    const buttonStart = document.createElement('button');
    buttonStart.textContent = Drupal.t('Start crawling');
    buttonStart.classList.add('button', 'button--primary');
    crawlControls.prepend(buttonStart);


    const updateProgValue = () => {
      const current = (currentPage - 1) * itemsPerPage + (maxRows - urls.length) + 1;
      const percent = Math.round(100 * current / totalMax);
      progressDescription.textContent = `${Drupal.t('Checked:')} ${current} / ${totalMax} ${Drupal.t('pages')}`;
      progressBar.style.setProperty('width', `${percent}%`);
      progressPercent.textContent = `${percent}%`;
    };

    let progressOnce;
    const initProgressTracker = () => {
      if (progressOnce) {
        return;
      }
      progressOnce = true;
      frameBucket.id = 'frame-bucket';
      wrapper.insertAdjacentElement('beforeend', frameBucket);
      updateProgValue();
    };

    const startButton = function () {
      if (buttonStart.dataset.state !== 'crawling') {
        paused = false;
        buttonStart.textContent = 'Pause crawl';
        buttonStart.dataset.state = 'crawling';
        wrapper.classList.remove('paused');
        wrapper.classList.add('crawling');
      } else {
        paused = true;
        buttonStart.textContent = 'Resume crawl';
        buttonStart.dataset.state = 'paused';
        wrapper.classList.add('paused');
      }
      initProgressTracker();
      crawl();
    };

    buttonStart.addEventListener('click', () => startButton());

    window.addEventListener('message', (e) => {
      if (e.origin !== window.location.origin || !e.data.done) {
        return; // Message from untrusted origin
      }
      const done = document.querySelector(`iframe[src="${e.data.done}"]`);
      window.ed11ySynced--;
      done?.parentNode.removeChild(done);
    });

    const nextPage = () => {
      if (window.ed11ySynced > 0) {
        window.setTimeout(() => {
          // Wait up to 20s for the last frames to finish.
          window.ed11ySynced = window.ed11ySynced - 0.0125;
          nextPage();
        }, 250);
        return;
      }
      if (paused) {
        return;
      }
      const nextPager = wrapper.querySelector('.pager [rel="next"]');
      if (nextPager) {
        window.setTimeout(function () {
          if (nextPager.href.indexOf('&speed') === -1) {
            nextPager.href = nextPager.href + `&speed=${crawlSpeed.value}`;
          } else {
            const url = new URL(nextPager.href);
            const addParams = {
              speed: `${crawlSpeed.value}`
            };
            const newParams = new URLSearchParams([
              ...Array.from(url.searchParams.entries()),
              ...Object.entries(addParams),
            ]).toString();
            nextPager.href = new URL(`${url.origin}${url.pathname}?${newParams}`);
          }
          nextPager.click();
        }, 1000);
      } else {
        // Done.
        wrapper.classList.add('done');
        wrapper.classList.add('paused');
        buttonStart.removeEventListener('click', () => startButton());
        exit.classList.add('button', 'button--primary');
        exit.textContent = Drupal.t('Back to dashboard');
        exit.focus();
      }
    };

    const crawl = () => {
      if (paused) {
        return;
      }
      // Limit concurrency.
      if (window.ed11ySynced > 3) {
        window.setTimeout(() => {
          // If we're stuck for more than 20s, give up and move on.
          window.ed11ySynced = window.ed11ySynced - 0.0125;
          crawl();
        }, parseInt(crawlSpeed.value));
        return;
      }

      window.setTimeout(() => {
        updateProgValue();
        const url = urls.shift();
        const newFrame = document.createElement('iframe');
        newFrame.setAttribute('sandbox', 'allow-same-origin allow-scripts');
        // @todo: expose preference for sandbox v CORS v less privileged account.
        // @todo: Redirects throw errors. Can we detect these and drop the result?
        newFrame.width = (100 / totalMax) + '%';
        window.ed11ySynced++;
        newFrame.setAttribute('src', url);
        frameBucket.appendChild(newFrame);

        if (urls.length > 0) {
          crawl();
        } else {
          nextPage();
        }

      }, parseInt(crawlSpeed.value));
    };


    if (parseInt(progress.getAttribute('value')) !== 1) {
      buttonStart.click();

    } else {
      progress.setAttribute('hidden', 'true');
    }
  }
};

