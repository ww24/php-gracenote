/**
 * player
 */

$(function () {
  $.getJSON("/fieldvalues").then(function (data) {
    Object.keys(data).forEach(function (type) {
      data[type].unshift({
        ID: "",
        VALUE: "未選択"
      });

      var $html = data[type].map(function (item) {
        var $option = $("<option>");
        $option.attr("value", item.ID);
        $option.text(item.VALUE);
        return $option;
      });

      $("#" + type).append($html);
    });
  });

  var $result = $("#result");

  $("#search").submit(function (e) {
    e.preventDefault();

    var querystring = $(this).serialize();
    // debug
    printURL("/create?" + querystring);

    $.getJSON("/create?" + querystring).then(function (data) {
      console.log(data);

      var $html = data.album.map(function (song) {
        var $li = $("<li class='play'>");
        var artist = ((song.TRACK[0].ARTIST || song.ARTIST)[0].VALUE).split(", ")[0];
        $li.data("metadata", {
          title: song.TRACK[0].TITLE[0].VALUE.trim(),
          artist: artist.trim()
        });
        var $ul = $("<ul>");
        $ul.append("<li>artist: " + artist + "</li>");
        $ul.append("<li>genre:  " + song.GENRE[0].VALUE + "</li>");
        $ul.append("<li>title:  " + song.TRACK[0].TITLE[0].VALUE + "</li>");
        $li.append($ul);
        return $li;
      });

      $result.html($html);
    });
  });

  var $artwork = $(".player-wrapper .artwork");
  var $player = $(".player-wrapper audio");

  $artwork.click(function () {
    var player = $player.get(0);
    
    // for debug
    p = player;

    if (player.paused) {
      player.play();
    } else {
      player.pause();
    }
  });

  $result.on("click", ".play", function () {
    var $elem = $(this);
    var metadata = $elem.data("metadata");
    console.log(metadata);

    var itunes_api = "/itunes?term=" + encodeURIComponent(metadata.title) + "&artist=" + encodeURIComponent(metadata.artist);
    // debug
    printURL(itunes_api);

    $.getJSON(itunes_api).then(function (data) {
      console.log(data);
      var track = data.correct_result || data.results[0];
      if (! track) {
        console.log(data);
        return console.error("track not found");
      }

      console.log(track.artistName, track.trackName);

      $artwork.css("background-image", "url(" + track.artworkUrl100 + ")");
      $player.attr("src", track.previewUrl);
      $player.get(0).play();
    });
  });

  function printURL(pathname, method) {
    method = method || "GET";
    console.info(method.toUpperCase() + " " + location.protocol + "//" + location.host + pathname);
  }
});
