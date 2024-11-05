define(['jquery', 'core/ajax'], function ($, Ajax) {
  "use strict";

  // Protect against spam
  var savyInitializedAlready = false;

  /**
   *
   * @param response {{script: string, stack: string, botId: string}}
   */
  function injectsavyScript(response) {
    const container = document.querySelector("#savy-container");
    const embedScript = document.createElement("script");
    embedScript.innerHTML = response.script;
    container.appendChild(embedScript);
    window.SAVY = window.CRIA[response.botId];
    injectMutationObserver();
    addSavyClickListener();
  }

  function addSavyClickListener() {
    const savyBtn = document.querySelector('#savy-btn');

    // Add the listener
    savyBtn.addEventListener('click', function () {
      // Enable SAVY
      window.SAVY.switch();

      // Click the popover to hide it
      const popover = document.querySelector(".btn-footer-popover");
      popover?.click();
    });

  }

  /**
   * The popover deletes the HTML for the savy btn, so we use a mutation-observer to re-add it each time
   */
  function injectMutationObserver() {
    const targetNode = document.body; // You can change this to a more specific parent element if necessary

    // Options for the observer (which mutations to observe)
    const config = {childList: true, subtree: true};

    // Callback function to execute when mutations are observed
    const callback = function (mutationsList, observer) {
      for (let mutation of mutationsList) {
        if (mutation.type === 'childList') {
          mutation.addedNodes.forEach(node => {
            if (node.id === 'savy-btn' || (node.querySelector && node.querySelector('#savy-btn'))) {
              addSavyClickListener();
            }
          });
        }
      }
    };

    // Create an instance of the observer with the callback function
    const observer = new MutationObserver(callback);

    // Start observing the target node for configured mutations
    observer.observe(targetNode, config);
  }

  /**
   * Whatever the mode currently is, it will be swapped with the opposite via API
   */
  function initsavy() {
    if (savyInitializedAlready) {
      return;
    }

    savyInitializedAlready = true;

    var request = {
      methodname: "theme_moove_launchsavy",
      args: {}
    }

    // Make call & reload
    Ajax.call([request])[0].then(function (response) {
      injectsavyScript(response)
    });

  }

  return {
    init: function () {
      window.initsavy = initsavy;
    }
  };
});
