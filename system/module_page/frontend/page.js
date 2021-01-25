document.addEventListener('DOMContentLoaded', function(){

  /* ───────────────────────────────────────────────────────────────────── */
  /* this code activate hover state on iOS devices                         */
  /* ───────────────────────────────────────────────────────────────────── */

  document.addEventListener('touchstart', function(){}, false);

  /* ───────────────────────────────────────────────────────────────────── */
  /* audio player                                                          */
  /* ───────────────────────────────────────────────────────────────────── */

  document.effQuerySelectorAll('audio[data-player-name="default"]').forEach(function(c_audio){
    var c_player       = document.createElement('x-audio-player');
    var c_button_play  = document.createElement('button');
    var c_timeline     = document.createElement('x-timeline');
    var c_trackpos     = document.createElement('x-track-position');
    var c_time         = document.createElement('x-time');
    var c_time_elpsd   = document.createElement('x-time-elapsed');
    var c_time_total   = document.createElement('x-time-total');
    var c_timerId      = null;
    var c_is_init      = null;
    var on_updateTimeInfo = function(){
      if (!isNaN(c_audio.duration)) {
        var time_cur =     Math.floor(c_audio.currentTime);
        var time_ttl =     Math.floor(c_audio.duration);
        var h_cur =        Math.floor(time_cur /    3600);
        var h_ttl =        Math.floor(time_ttl /    3600);
        var m_cur = ('0' + Math.floor(time_cur / 60 % 60)).slice(-2);
        var m_ttl = ('0' + Math.floor(time_ttl / 60 % 60)).slice(-2);
        var s_cur = ('0' + Math.floor(time_cur      % 60)).slice(-2);
        var s_ttl = ('0' + Math.floor(time_ttl      % 60)).slice(-2);
        c_trackpos.style.width = Math.floor(c_audio.currentTime / c_audio.duration * 100) + '%';
        c_time_elpsd.innerText = h_cur + ':' + m_cur + ':' + s_cur;
        c_time_total.innerText = h_ttl + ':' + m_ttl + ':' + s_ttl;
        if (!c_is_init) {
          c_is_init = true;
          c_player.setAttribute('data-is-loadedmetadata', '');
          c_timeline.addEventListener('click', function(event){
            var timelineX = event.clientX + document.documentElement.scrollLeft - c_timeline.offsetLeft;
            c_audio.currentTime = c_audio.duration * (timelineX / c_timeline.clientWidth);
          });
        }
      }
    }
    c_player.append(c_button_play, c_timeline, c_time);
    c_timeline.append(c_trackpos);
    c_time.append(c_time_elpsd, c_time_total);
    c_audio.parentNode.insertBefore(c_player, c_audio);
    c_audio.controls = false;
    c_time_elpsd.innerText = '‐ : ‐ ‐';
    c_time_total.innerText = '‐ : ‐ ‐';
    c_button_play.value = 'play';
 /* events */
    c_audio.addEventListener('loadedmetadata', on_updateTimeInfo);
    c_audio.addEventListener('timeupdate',     on_updateTimeInfo);
    c_audio.addEventListener('play',        function(){c_player.   setAttribute('data-is-playing', '');});
    c_audio.addEventListener('pause',       function(){c_player.removeAttribute('data-is-playing');});
    c_audio.addEventListener('ended',       function(){c_player.removeAttribute('data-is-playing'); /* IE fix → */ c_audio.pause();});
    c_button_play.addEventListener('click', function(){
      if (c_audio.paused) c_audio.play ();
      else                c_audio.pause();
    });
    c_audio.addEventListener('progress', function(){
      clearTimeout(c_timerId);
      c_player.setAttribute('data-is-progressing', '');
      c_timerId = setTimeout(function(){
        c_player.removeAttribute('data-is-progressing');
      }, 1000);
    });
  });

  /* ───────────────────────────────────────────────────────────────────── */
  /* gallery player                                                         */
  /* ───────────────────────────────────────────────────────────────────── */

  document.effQuerySelectorAll('x-gallery[data-player-name="default"]').forEach(function(c_gallery){
    var c_player              = document.createElementWithAttribute('x-gallery-player', {'aria-hidden' : 'true'});
    var c_player_thumbnails   = document.createElement('x-thumbnails');
    var c_player_button_l     = document.createElement('x-button-l');
    var c_player_button_r     = document.createElement('x-button-r');
    var c_player_button_c     = document.createElement('x-button-c');
    var c_player_viewing_area = document.createElement('x-viewing-area');
    c_player.append(c_player_thumbnails, c_player_button_l, c_player_button_r, c_player_button_c, c_player_viewing_area);
    c_gallery.prepend(c_player);
    c_player_button_l.addEventListener('click', function(){});
    c_player_button_r.addEventListener('click', function(){});
    c_player_button_c.addEventListener('click', function(){                          c_player.setAttribute('aria-hidden', 'true'); document.body.removeAttribute('data-is-active-gallery-player');});
    document.addEventListener('keypress', function(event){if (event.charCode === 27) c_player.setAttribute('aria-hidden', 'true'); document.body.removeAttribute('data-is-active-gallery-player');});
 /* process each gallery item */
    c_gallery.effQuerySelectorAll('x-item').forEach(function(c_item){
      var c_thumbnail = document.createElementWithAttribute('x-thumbnail', {
          'data-type' : c_item.getAttribute('data-type'),
          'data-num'  : c_item.getAttribute('data-num')});
      switch (c_item.getAttribute('data-type')) {
        case 'picture':
          var c_img = c_item.getElementsByTagName('img')[0];
          var c_url = new EffURL(c_img.getAttribute('src'));
              c_url.queryArgDelete('thumb');
              c_url.queryArgInsert('thumb', 'small');
          var c_src_small = c_url.tinyGet();
              c_url.queryArgDelete('thumb');
              c_url.queryArgInsert('thumb', 'big');
          var c_src_big = c_url.tinyGet();
          var c_thumbnail_img = document.createElementWithAttribute('img', {'src' : c_src_small});
          c_thumbnail.setAttribute('data-src-big', c_src_big);
          c_thumbnail.append(c_thumbnail_img);
          c_player_thumbnails.append(c_thumbnail);
       /* when click on item in gallery */
          c_item.addEventListener('click', function(event){
            event.preventDefault();
            c_player.removeAttribute('aria-hidden');
            document.body.setAttribute('data-is-active-gallery-player', 'true');
            c_player_thumbnails.effQuerySelectorAll(
              'x-thumbnail[data-num="' + this.getAttribute('data-num') + '"]'
            )[0].click();
          });
        /* when click on thumbnail in player */
          c_thumbnail.addEventListener('click', function(){
            c_player_thumbnails.effQuerySelectorAll('[aria-selected="true"]').forEach(function(c_selected){c_selected.removeAttribute('aria-selected');});
            c_thumbnail.setAttribute('aria-selected', 'true');
            c_player_viewing_area.innerHTML = '';
            switch (this.getAttribute('data-type')) {
              case 'picture':
                c_player_viewing_area.append(
                  document.createElementWithAttribute('img', {'src' : this.getAttribute('data-src-big')})
                );
                break;
            }
          });
          break;
      }
    });
  });

});