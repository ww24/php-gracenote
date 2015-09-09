/**
 * Controllers
 *
 */

angular.module("starter.controllers", [])
.controller("PlayerCtrl", function($scope, $http, $ionicLoading, $ionicModal, Playlist, Settings) {
  $scope.Playlist = Playlist;
  console.log(Playlist);

  $scope.track = null;

  $ionicModal.fromTemplateUrl("templates/modal-player.html", {
    scope: $scope,
    animation: "slide-in-up"
  }).then(function (modal) {
    console.log(modal);
    $scope.modal = modal;
  });

  $scope.$watch("track.audio.paused", function (new_val) {
    if ($scope.track === null) {
      return;
    }
    if (new_val) {
      $scope.track.icon = "ion-play";
    } else {
      $scope.track.icon = "ion-pause";
    }
  });

  // play music
  $scope.play = function () {
    var audio = $scope.track.audio;

    console.log(a = audio);

    if (audio.paused) {
      audio.play();
    } else {
      audio.pause();
    }
  };

  // stop music when modal hidden
  $scope.$on("modal.hidden", function () {
    var audio = $scope.track.audio;
    if (audio === null) {
      return;
    }
    $scope.track.audio.pause();
  });

  $scope.seek = function () {
    console.log("seek");
    var audio = $scope.track.audio;
    audio.currentTime = $scope.track.time / 1000;
  };

  // select a track
  $scope.select = function () {
    var song = this.song;

    $ionicLoading.show({
      template: "Loading..."
    });

    $http.get(Settings.endpoint + "/itunes", {
      params: {
        term: song.TRACK[0].TITLE[0].VALUE.trim(),
        artist: ((song.TRACK[0].ARTIST || song.ARTIST)[0].VALUE).split(", ")[0].trim()
      }
    }).then(function (res) {
      console.log(res.data);
      var data = res.data;

      $ionicLoading.hide();
      $scope.modal.show();

      var track = data.correct_result || data.results[0];
      if (! track) {
        console.log(data);
        return console.error("track not found");
      }

      console.log(track.artistName, track.trackName);

      console.log(track.previewUrl);

      $scope.radio_id = data.radio_id;
      $scope.track = {
        url: track.previewUrl,
        artist: ((song.TRACK[0].ARTIST || song.ARTIST)[0].VALUE).split(", ")[0],
        title: song.TRACK[0].TITLE[0].VALUE,
        art: track.artworkUrl100,
        // play or pause icon
        icon: "ion-play",
        audio: null,
        time: 0,
        time_end: 0,
        gnid: song.TRACK[0].GN_ID
      };

      var audio = new Audio($scope.track.url);
      audio.autoplay = false;
      audio.preload = "auto";
      audio.load();
      audio.addEventListener("progress", function () {
        console.log("progress");
        $scope.track.time_end = Math.floor(audio.seekable.end(0) * 1000);
      });
      audio.addEventListener("ended", function () {
        console.log("ended");
        Playlist.feedback("track_played", $scope.track.gnid);
      });

      audio.addEventListener("timeupdate", function () {
        $scope.$apply(function () {
          $scope.track.time = Math.floor(audio.currentTime * 1000);
        });
      });

      $scope.track.audio = audio;
    });
  };
})
.controller("SettingsCtrl", function($scope, $http, $timeout, $ionicLoading, Settings, Playlist) {
  $http.get(Settings.endpoint).then(function (res) {
    console.log("UserID:", res.data.user_id);

    return $http.get(Settings.endpoint + "/fieldvalues");
  }).then(function (res) {
    console.log(res.data);

    $scope.genres = [{ID:"", VALUE:"未選択"}].concat(res.data.genre);
    $scope.moods = [{ID:"", VALUE:"未選択"}].concat(res.data.mood);
    $scope.eras = [{ID:"", VALUE:"未選択"}].concat(res.data.era);
  });

  $scope.Settings = Settings;

  $scope.save = function () {
    if (Settings.endpoint.slice(-1) === "/") {
      Settings.endpoint = Settings.endpoint.slice(0, -1);
    }

    Settings.save();
    Playlist.create();

    $ionicLoading.show({
      template: "Saved"
    });
    $timeout(function () {
      $ionicLoading.hide();
    }, 500);
  };
});
