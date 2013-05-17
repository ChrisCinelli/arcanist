<?php

final class BBReviewerListener extends PhutilEventListener {

  public function register() {
    $this->listen(ArcanistEventType::TYPE_DIFF_WILLBUILDMESSAGE);
  }

  public function handleEvent(PhutilEvent $event) {
    
    $fields = $event->getValue('fields');
    
    $user = $event->getValue('workflow')->getUserName();

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "http://ndev-chris.bloomboard.com/Test/getReviewer/".$user);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $output = curl_exec($ch);
    curl_close($ch);

    $r = json_decode($output);
    
    $more = $event->getValue('workflow')->getConduit()->callMethodSynchronous(
      'user.query',
      array(
        'usernames' => array(
          $r->reviewer1,
          $r->reviewer2,
        ),
      ));

    $phids = idx($fields, 'reviewerPHIDs', array());

    foreach ($more as $user) {
      $phids[] = $user['phid'];
    }
    $phids = array_unique($phids);

    $fields['reviewerPHIDs'] = $phids;

    $event->setValue('fields', $fields);
  }

}