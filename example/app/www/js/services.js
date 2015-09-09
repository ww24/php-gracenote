/**
 * Service
 *
 */

angular.module("starter.services", [])
.filter("time_mmss", function () {
  return function (time) {
    return moment(Number(time)).format("mm:ss");
  };
})
.factory("Settings", function () {
  var prefix = "Settings:";

  var data = {
    endpoint: localStorage.getItem("Settings:endpoint") || "http://localhost:8080",
    artist: null,
    title: null,
    genre: null,
    mood: null,
    era: null,
    save: function () {
      var data = this;
      var keys = Object.keys(data).map(function (key) {
        if (typeof data[key] === "function") {
          return false;
        }

        localStorage.setItem(prefix + key, data[key]);

        return key;
      }).filter(function (value) {return value});

      return keys;
    }
  };

  Object.keys(data).forEach(function (key) {
    if (typeof data[key] === "function" || data[key] !== null) {
      return false;
    }

    data[key] = localStorage.getItem(prefix + key) || "";
  });

  return data;
})
.factory("Playlist", function (Settings, $http) {
  var data = {};

  data.create = function () {
    if (Settings.endpoint.length > 0 && (Settings.genre.length > 0 || Settings.mood.length > 0 || Settings.era.length > 0 || Settings.artist.length > 0)) {
      if (Settings.title.length > 0 && Settings.artist.length === 0) {
        return false;
      }

      $http.get(Settings.endpoint + "/create", {
        params: {
          artist_name: Settings.artist,
          track_title: Settings.title,
          genre: Settings.genre,
          mood: Settings.mood,
          era: Settings.era,
          return_count: 5
        }
      }).then(function (res) {
        data.radio_id = res.data.radio_id;
        data.songs = res.data.album;
      });

      return true;
    }

    return false;
  };

  // Gracenote feedback event
  data.feedback = function (event_name, gnid) {
    $http.get(Settings.endpoint + "/feedback", {
      params: {
        radio_id: data.radio_id,
        event: event_name + "_" + gnid
      }
    }).then(function (res) {
      data.radio_id = res.data.radio_id;
      data.songs = res.data.album;
    });
  };

  console.log("Create playlist:", data.create());

  return data;
});
