<?php
require 'includes/header.php';
force_id();

if (isset($_POST['action'])) {
    $action = $_POST['action'];
    if ($action === 'subscribe') {
        $link->insert('chrome_tokens', array(
            'uid' => $_SESSION['UID'],
            'subscription_id' => $_POST['subscription_id'],
            'endpoint' => $_POST['endpoint'],
        ));
    } elseif ($action === 'unsubscribe') {
        $link->db_exec('DELETE FROM chrome_tokens WHERE uid = %1 AND subscription_id = %2', $_SESSION['UID'], $_POST['subscription_id']);
    } elseif ($action === 'test') {
        sendMessageToChrome($_SESSION['UID'], 'subscribed', null);
    }
    die();
}

if ($_SERVER['HTTPS'] != 'on') {
    echo 'Push notifications are only availble when using HTTPS';
    require 'includes/footer.php';
    die();
}

$additional_head = '<link rel="manifest" href="'.DOMAIN.'/chrome-push/manifest.json">';
?>
<script>
var isPushEnabled = false;

window.addEventListener('load', function() {  
  var pushButton = document.querySelector('.js-push-button');  
  var testButton = document.querySelector('.js-test-button');  
  pushButton.addEventListener('click', function() {  
    if (isPushEnabled) {  
      unsubscribe();  
    } else {  
      subscribe();  
    }  
  });
  
  testButton.addEventListener('click', function() {  
    $.post("", {action: 'test'}); 
  });

  // Check that service workers are supported, if so, progressively  
  // enhance and add push messaging support, otherwise continue without it.  
  if ('serviceWorker' in navigator) {
    navigator.serviceWorker.register('/push-service-worker.js')  
    .then(initialiseState);  
  } else {  
    console.warn('Service workers aren\'t supported in this browser.');  
  }  
});

// Once the service worker is registered set the initial state  
function initialiseState() {  
  // Are Notifications supported in the service worker?  
  if (!('showNotification' in ServiceWorkerRegistration.prototype)) {  
    alert('Notifications aren\'t supported.');  
    return;  
  }

  // Check the current Notification permission.  
  // If its denied, it's a permanent block until the  
  // user changes the permission  
  if (Notification.permission === 'denied') {  
    alert('The user has blocked notifications.');  
    return;  
  }

  // Check if push messaging is supported  
  if (!('PushManager' in window)) {  
    alert('Push messaging isn\'t supported.');  
    return;  
  }

  // We need the service worker registration to check for a subscription  
  navigator.serviceWorker.ready.then(function(serviceWorkerRegistration) {  
    // Do we already have a push message subscription?  
    serviceWorkerRegistration.pushManager.getSubscription()  
      .then(function(subscription) {  
        // Enable any UI which subscribes / unsubscribes from  
        // push messages.  
        var pushButton = document.querySelector('.js-push-button');  
        pushButton.disabled = false;

        if (!subscription) {  
          // We aren't subscribed to push, so set UI  
          // to allow the user to enable push  
          return;  
        }
        
        // Keep your server in sync with the latest subscriptionId
        sendSubscriptionToServer(subscription);

        // Set your UI to show they have subscribed for  
        // push messages  
        pushButton.textContent = 'Disable Push Messages';  
        isPushEnabled = true;  
      })  
      .catch(function(err) {  
        console.warn('Error during getSubscription()', err);  
      });  
  });  
}

function subscribe() {  
  // Disable the button so it can't be changed while  
  // we process the permission request  
  var pushButton = document.querySelector('.js-push-button');  
  pushButton.disabled = true;

  navigator.serviceWorker.ready.then(function(serviceWorkerRegistration) {  
    serviceWorkerRegistration.pushManager.subscribe()  
      .then(function(subscription) {  
        // The subscription was successful  
        isPushEnabled = true;  
        pushButton.textContent = 'Disable Push Messages';  
        pushButton.disabled = false;      
          
        // TODO: Send the subscription.subscriptionId and   
        // subscription.endpoint to your server  
        // and save it to send a push message at a later date   
        return sendSubscriptionToServer(subscription);  
      })  
      .catch(function(e) {  
        if (Notification.permission === 'denied') {  
          // The user denied the notification permission which  
          // means we failed to subscribe and the user will need  
          // to manually change the notification permission to  
          // subscribe to push messages  
          console.warn('Permission for Notifications was denied');  
          pushButton.disabled = true;  
        } else {  
          // A problem occurred with the subscription; common reasons  
          // include network errors, and lacking gcm_sender_id and/or  
          // gcm_user_visible_only in the manifest.  
          console.error('Unable to subscribe to push.', e);  
          pushButton.disabled = false;  
          pushButton.textContent = 'Enable Push Messages';  
        }  
      });  
  });  
}

function unsubscribe() {  
  var pushButton = document.querySelector('.js-push-button');  
  pushButton.disabled = true;

  navigator.serviceWorker.ready.then(function(serviceWorkerRegistration) {  
    // To unsubscribe from push messaging, you need get the  
    // subscription object, which you can call unsubscribe() on.  
    serviceWorkerRegistration.pushManager.getSubscription().then(  
      function(pushSubscription) {  
        // Check we have a subscription to unsubscribe  
        if (!pushSubscription) {  
          // No subscription object, so set the state  
          // to allow the user to subscribe to push  
          isPushEnabled = false;  
          pushButton.disabled = false;  
          pushButton.textContent = 'Enable Push Messages';  
          return;  
        }  
          
        var subscriptionId = pushSubscription.subscriptionId;  
        $.post("", {
			action: "unsubscribe",
			subscription_id: subscriptionId
		});

        // We have a subscription, so call unsubscribe on it  
        pushSubscription.unsubscribe().then(function(successful) {  
          pushButton.disabled = false;  
          pushButton.textContent = 'Enable Push Messages';  
          isPushEnabled = false;  
        }).catch(function(e) {  
          // We failed to unsubscribe, this can lead to  
          // an unusual state, so may be best to remove   
          // the users data from your data store and   
          // inform the user that you have done so

          console.log('Unsubscription error: ', e);  
          pushButton.disabled = false;
          pushButton.textContent = 'Enable Push Messages'; 
        });  
      }).catch(function(e) {  
        console.error('Error thrown while unsubscribing from push messaging.', e);  
      });  
  });  
}

function sendSubscriptionToServer(subscription) {
	console.log(subscription);
	$.post("", {
		action: "subscribe",
		subscription_id: subscription.subscriptionId,
		endpoint: subscription.endpoint
	});
}
</script>
<b>Note: This feature is currently in beta and only expected to work on Chrome (including Chrome for Android)</b><br />
<button class="js-push-button" disabled> 
  Enable Push Messages  
</button><br />
<button class="js-test-button"> 
  Test Push Messages
</button>
<?php

require 'includes/footer.php';
