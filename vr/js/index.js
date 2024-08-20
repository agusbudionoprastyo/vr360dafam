var vr_mode = false, poi_open = false, poi_closing = false, timeout_poi_closing = null, progress_circle = null, interval_loading = null;
var interval_access_time_avg = null, access_time_avg = 0;

var userAgent = navigator.userAgent.toLowerCase();
if (userAgent.indexOf(' electron/') === -1) {
    if( window.location.protocol == 'file:' ){
        alert("Due to browser security restrictions, a web server must be used locally as well.");
        window.stop();
        throw new Error("Due to browser security restrictions, a web server must be used locally as well.");
    }
}

progress_circle = new ProgressBar.Circle('.progress-circle', {
    strokeWidth: 5,
    easing: 'easeInOut',
    duration: window.assets_interval,
    color: '#ffffff',
    trailColor: 'rgba(255,255,255,0.15)',
    trailWidth: 5,
    text: {
        autoStyleContainer: false
    },
    step: function(state, circle) {
        var value = Math.round(circle.value() * 100);
        if (value === 0) {
            circle.setText('');
        } else {
            circle.setText(value);
        }
    }
});
progress_circle.animate(0.01);
var perecentage_loading = 0.01;
interval_loading = setInterval(function () {
    if(perecentage_loading===0.99) {
        clearInterval(interval_loading);
    } else {
        perecentage_loading += 0.01;
        if(perecentage_loading>=1) { perecentage_loading=1; }
        progress_circle.animate(perecentage_loading);
    }
},window.assets_interval);

AFRAME.registerComponent('scenelistener',{
    init:function(){
        this.el.sceneEl.addEventListener('renderstart',function(){
            clearInterval(interval_loading);
            progress_circle.animate(1);
            setTimeout(function() {
                document.getElementsByClassName('loading')[0].style.opacity = 0;
                document.getElementsByClassName('loading')[0].style.pointerEvents = 'none';
                document.getElementById('vt_scene').style.opacity = 1;
                document.getElementById('vt_scene').style.pointerEvents = 'initial';
                view_room_name('');
                set_room_statistics();
                clearInterval(interval_access_time_avg);
                var room_target = document.getElementById('r_'+window.id_frist_room);
                if(room_target.tagName=='VIDEO') {
                    room_target.currentTime=0;
                    room_target.play();
                }
                access_time_avg = 0;
                interval_access_time_avg = setInterval(function () {
                    access_time_avg = access_time_avg + 1;
                }, 1000);
                var background_music = document.getElementById('background_music');
                if(background_music) {
                    background_music.play().catch(function() {
                        window.addEventListener('click',function(){
                            if (background_music.currentTime==0 && background_music.paused) {
                                try {
                                    background_music.play().catch(function() {});
                                } catch (e) {}
                            }
                        });
                    });
                }
                if(exit_button) {
                    document.getElementById('exit_vr_button').style.display = 'block';
                }
                if(isMobile()) {
                    try {
                        var scene = document.querySelector('a-scene');
                        scene.enterVR();
                    } catch (e) {}
                }
            },1000);
        });
        this.el.sceneEl.addEventListener('enter-vr',function(){
            if (AFRAME.utils.device.checkHeadsetConnected()) {
                var cursor = document.getElementById("cursor-visual");
                cursor.setAttribute('position','0.02 0 -0.9');
                vr_mode = true;
                if(exit_button) {
                    document.getElementById('exit_vr_button').style.display = 'none';
                }
            }
        });
        this.el.sceneEl.addEventListener('exit-vr',function(){
            var cursor = document.getElementById("cursor-visual");
            cursor.setAttribute('position','0 0 -0.9');
            vr_mode = false;
            if(exit_button) {
                document.getElementById('exit_vr_button').style.display = 'block';
            }
        });
    }
});

AFRAME.registerComponent('hotspots', {
    init: function () {
        this.el.addEventListener('reloadspots', function (evt) {
            var currspots_markers = evt.detail.currspots;
            var currspots_pois = evt.detail.currspots.replace('markers','pois');
            var newspots_markers = evt.detail.newspots;
            var newspots_pois = evt.detail.newspots.replace('markers','pois');
            var currspotgroup_markers = document.getElementById(currspots_markers);
            if(currspotgroup_markers) {
                currspotgroup_markers.setAttribute("scale", "0 0 0");
            }
            var currspotgroup_pois = document.getElementById(currspots_pois);
            if(currspotgroup_pois) {
                currspotgroup_pois.setAttribute("scale", "0 0 0");
            }
            var newspotgroup_markers = document.getElementById(newspots_markers);
            if(newspotgroup_markers) {
                newspotgroup_markers.setAttribute("scale", "1 1 1");
            }
            var newspotgroup_pois = document.getElementById(newspots_pois);
            if(newspotgroup_pois) {
                newspotgroup_pois.setAttribute("scale", "1 1 1");
            }
        });
    }
});
AFRAME.registerComponent('spot', {
    schema: {
        object: {type: "string", default: ""},
        type: {type: "string", default: ""},
        linkto: {type: "string", default: ""},
        spotgroup: {type: "string", default: ""},
        room_name: {type: "string", default: ""}
    },
    init: function () {
        var data = this.data;
        switch(data.object) {
            case 'marker':
                this.el.setAttribute("material", "src", "#marker");
                break;
            case 'poi':
                this.el.setAttribute("material", "src", "#poi_"+data.type);
                break;
        }
        this.el.addEventListener('click', function () {
            var close_btn, close_btn3d, current_src;
            switch(data.object) {
                case 'marker':
                    var cam=document.getElementById("cam");
                    cam.emit("zoomin");
                    setTimeout(function () {
                        var fp=document.getElementById("camfadeplane");
                        fp.emit("camFadeIn");
                    },300);
                    var click_target = this;
                    setTimeout(function () {
                        var sky = document.getElementById("skybox");
                        sky.setAttribute("src", data.linkto);
                        sky.setAttribute("data-id-room", data.linkto.replace('#r_',''));
                        set_room_statistics();
                        set_room_time_statistics();
                        clearInterval(interval_access_time_avg);
                        access_time_avg = 0;
                        interval_access_time_avg = setInterval(function () {
                            access_time_avg = access_time_avg + 1;
                        }, 1000);
                        var videos = document.getElementsByClassName('panorama_video');
                        for (var i = 0; i < videos.length; i++) {
                            videos.item(i).pause();
                            videos.item(i).currentTime=0;
                        }
                        var room_target = document.getElementById(data.linkto.replace('#', ''));
                        if(room_target.tagName=='VIDEO') {
                            room_target.currentTime=0;
                            room_target.play();
                        }
                        var room_target_north = room_target.getAttribute('data-north');
                        var rotation = 0 + ' ' + (room_target_north) + ' ' + 0;
                        sky.setAttribute("rotation", rotation);
                        var spotcomp = document.getElementById("spots");
                        spotcomp.setAttribute("rotation", rotation);
                        var currspots = click_target.parentElement.parentElement.getAttribute("id");
                        spotcomp.emit('reloadspots', {newspots: data.spotgroup, currspots: currspots});
                        var camera = document.getElementById("cam_wrapper");
                        if(camera) {
                            camera.setAttribute("rotation", "0 90 0");
                        }
                        var cam=document.getElementById("cam");
                        cam.setAttribute("camera","fov:90");
                        cam.emit("zoomout");
                        var fp=document.getElementById("camfadeplane");
                        fp.emit("camFadeOut");
                        view_room_name(data.room_name);
                    },600);
                    break;
                case 'poi':
                    if(data.type!=='audio') {
                        var pois = document.getElementsByClassName('poi_icon');
                        for (var i = 0; i < pois.length; i++) {
                            pois[i].setAttribute('scale','0 0 0');
                        }
                        var markers = document.getElementsByClassName('marker_icon');
                        for (var i = 0; i < markers.length; i++) {
                            markers[i].setAttribute('scale','0 0 0');
                        }
                    }
                    var poi = document.getElementById(data.linkto.replace('#', ''));
                    var id_poi = data.linkto.replace('#poi_content_','');
                    set_poi_statistics(id_poi);
                    switch(data.type) {
                        case 'object3d':
                            var object = document.getElementById('object3d_'+id_poi);
                            poi.setAttribute('visible','true');
                            object.emit('model-autosize');
                            close_btn_3d = document.getElementById('close_object3d_'+id_poi);
                            close_btn_3d.setAttribute('scale','1.5 1.5 1.5');
                            break;
                        case 'html':
                            var width = poi.getAttribute('data-width');
                            var height = poi.getAttribute('data-height');
                            poi.setAttribute('scale',width+' '+height+' 1');
                            poi.setAttribute('visible','true');
                            poi.emit('plane-padding');
                            break;
                        case 'audio':
                        case 'video360':
                            break;
                        default:
                            var width = poi.getAttribute('data-width');
                            var height = poi.getAttribute('data-height');
                            poi.setAttribute('scale',width+' '+height+' 1');
                            break;
                    }
                    var sky = document.getElementById("skybox");
                    var overlay = document.getElementById('overlay');
                    switch(data.type) {
                        case 'html':
                        case 'audio':
                            break;
                        case 'video360':
                            current_src = sky.getAttribute("src");
                            sky.setAttribute("src", '#p_'+id_poi);
                            close_btn = document.getElementById('close_video360');
                            close_btn.setAttribute('scale','1 1 1');
                            var msg_close_video = document.getElementById('msg_close_video');
                            msg_close_video.setAttribute('scale','1 1 1');
                            setTimeout(function() {
                                msg_close_video.setAttribute('scale','0 0 0');
                            },2000);
                            break;
                        default:
                            overlay.setAttribute('opacity','0.75');
                            break;
                    }
                    var background_music = document.getElementById('background_music');
                    switch(data.type) {
                        case 'video':
                            var video = document.getElementById('p_'+id_poi);
                            video.currentTime = 0;
                            video.play();
                            if(background_music) {
                                background_music.volume = 0;
                                background_music.pause();
                            }
                            break;
                        case 'audio':
                            var audio = document.getElementById('p_'+id_poi);
                            audio.addEventListener("ended", function(){
                                if(background_music) {
                                    background_music.volume = 1;
                                    background_music.play();
                                }
                            });
                            audio.currentTime = 0;
                            audio.play();
                            if(background_music) {
                                background_music.volume = 0;
                                background_music.pause();
                            }
                            break;
                        case 'video360':
                            var video360 = document.getElementById('p_'+id_poi);
                            video360.currentTime = 0;
                            video360.play();
                            if(background_music) {
                                background_music.volume = 0;
                                background_music.pause();
                            }
                            break;
                    }
                    poi_open = true;
                    var cur = document.getElementById("cursor-visual");
                    var cur_bg = document.getElementById("cursor-visual-bg");
                    switch(data.type) {
                        case 'audio':
                            poi_open = false;
                            cur.emit("stopFuse");
                            break;
                        case 'object3d':
                            close_btn3d = document.getElementById('close_object3d_'+id_poi);
                            close_btn3d.addEventListener('mouseenter', function () {
                                poi_closing = true;
                                cur.emit("stopFuse");
                                timeout_poi_closing = setTimeout(function() {
                                    poi.setAttribute('visible',false);
                                    close_btn3d.setAttribute('scale','0 0 0');
                                    overlay.setAttribute('opacity','0');
                                    poi_open = false;
                                    poi_closing = false;
                                    for (var i = 0; i < pois.length; i++) {
                                        pois[i].setAttribute('scale','1 1 1');
                                    }
                                    var markers = document.getElementsByClassName('marker_icon');
                                    for (var i = 0; i < markers.length; i++) {
                                        markers[i].setAttribute('scale','1 1 1');
                                    }
                                },400);
                            });
                            close_btn3d.addEventListener('mouseleave', function () {
                                if(poi_closing) {
                                    poi_closing = false;
                                    clearTimeout(timeout_poi_closing);
                                    cur.emit("startFuseFix");
                                }
                            });
                            break;
                        case 'video360':
                            close_btn = document.getElementById('close_video360');
                            close_btn.addEventListener('mouseenter', function () {
                                poi_closing = true;
                                cur.emit("stopFuse");
                                timeout_poi_closing = setTimeout(function() {
                                    close_btn.setAttribute('scale','0 0 0');
                                    poi_open = false;
                                    poi_closing = false;
                                    video360.currentTime = 0;
                                    video360.pause();
                                    sky.setAttribute("src", current_src);
                                    if(background_music) {
                                        background_music.volume = 1;
                                        background_music.play();
                                    }
                                    for (var i = 0; i < pois.length; i++) {
                                        pois[i].setAttribute('scale','1 1 1');
                                    }
                                    var markers = document.getElementsByClassName('marker_icon');
                                    for (var i = 0; i < markers.length; i++) {
                                        markers[i].setAttribute('scale','1 1 1');
                                    }
                                },400);
                            });
                            close_btn.addEventListener('mouseleave', function () {
                                if(poi_closing) {
                                    poi_closing = false;
                                    clearTimeout(timeout_poi_closing);
                                    cur.emit("startFuseFix");
                                }
                            });
                            break;
                        default:
                            poi.addEventListener('mouseenter', function () {
                                cur.setAttribute('material','opacity:0.3');
                                cur_bg.setAttribute('material','opacity:0.3');
                                if(poi_closing) {
                                    poi_closing = false;
                                    clearTimeout(timeout_poi_closing);
                                    cur.emit("startFuseFix");
                                }
                            });
                            poi.addEventListener('mouseleave', function () {
                                poi_closing = true;
                                cur.setAttribute('material','opacity:1');
                                cur_bg.setAttribute('material','opacity:1');
                                cur.emit("stopFuse");
                                timeout_poi_closing = setTimeout(function() {
                                    poi.setAttribute('scale','0 0 0');
                                    overlay.setAttribute('opacity','0');
                                    switch(data.type) {
                                        case 'video':
                                            video.pause();
                                            video.currentTime = 0;
                                            var background_music = document.getElementById('background_music');
                                            if(background_music) {
                                                background_music.volume = 1;
                                                background_music.play();
                                            }
                                            break;
                                    }
                                    poi_open = false;
                                    poi_closing = false;
                                    for (var i = 0; i < pois.length; i++) {
                                        pois[i].setAttribute('scale','1 1 1');
                                    }
                                    var markers = document.getElementsByClassName('marker_icon');
                                    for (var i = 0; i < markers.length; i++) {
                                        markers[i].setAttribute('scale','1 1 1');
                                    }
                                },400);
                            });
                            break;
                    }
                    break;
            }
        });
        this.el.addEventListener('mouseleave', function () {
            if(!poi_open) {
                var cur = document.getElementById("cursor-visual");
                cur.emit("stopFuse");
            }
        });
        this.el.addEventListener('mouseenter', function () {
            var cur = document.getElementById("cursor-visual");
            cur.emit("startFuse");
        });
    }
});

AFRAME.registerComponent("natural-size", {
    schema: {
        width: {
            type: "number",
            default: 0,
        },
        height: {
            type: "number",
            default: 0,
        },
        depth: {
            type: "number",
            default: 0,
        },
    },
    init: function () {
        this.el.addEventListener("model-loaded", function() {

        });
        this.el.addEventListener("model-autosize", this.rescale.bind(this));
    },
    rescale: function () {
        var el = this.el;
        var autosized = el.hasAttribute("autosized");
        if(!autosized) {
            var data = this.data;
            var model = el.object3D;
            var box = new THREE.Box3().setFromObject(model);
            var size = getDimensions(box);
            if (!size.x && !size.y && !size.z) {
                return;
            }
            var scale = 1;
            if (data.width!==0) {
                scale = data.width / size.x;
            } else if (data.height!==0) {
                scale = data.height / size.y;
            } else if (data.depth!==0) {
                scale = data.depth / size.z;
            }
            if((scale!==-0) && (scale!==0)) {
                el.setAttribute("scale", scale+' '+scale+' '+scale);
                el.setAttribute("autosized");
            }
        }
    }
});

function getDimensions(box) {
    var x = box.max.x - box.min.x;
    var y = box.max.y - box.min.y;
    var z = box.max.z - box.min.z;
    return {x,y,z};
}

AFRAME.registerComponent("planepadder", {
    schema: {
        addPadding: {
            type: "boolean",
            default: false
        },
        padding: {
            type: "number",
            default: 0.01
        }
    },
    init: function() {
        this.el.addEventListener("plane-padding", this.add_padding.bind(this));
    },
    add_padding: function() {
        var data = this.data;
        var el = this.el;
        if (Object.keys(data).length === 0) {
            return;
        }
        if (data.addPadding === true) {
            el.getObject3D("mesh").geometry = new THREE.PlaneGeometry(
                el.components.geometry.data.width + el.components.planepadder.data.padding,
                el.components.geometry.data.height + el.components.planepadder.data.padding,
                1,1
            );
            el.setAttribute("planepadder", "addPadding: false");
        }
    }
});

function view_room_name(name) {
    var room_name = document.getElementById('room_name');
    if(name!=='') {
        room_name.setAttribute("value",name);
    }
    room_name.setAttribute("scale","1 1 1");
    room_name.emit('roomNameFadeIn');
    setTimeout(function() {
        room_name.emit('roomNameFadeOut');
        setTimeout(function() {
            room_name.setAttribute("scale","0 0 0");
        },400);
    },1500);
}

window.set_poi_statistics = function(id) {
    if(window.export===1) { return; }
    var postObj = {
        type: 'poi',
        id: id,
        ip_visitor: window.ip_visitor
    };
    var post = JSON.stringify(postObj);
    var url = "../viewer/ajax/set_statistics.php";
    var xhr = new XMLHttpRequest();
    xhr.open('POST', url, true);
    xhr.setRequestHeader('Content-type', 'application/json; charset=UTF-8');
    xhr.send(post);
};

window.set_room_statistics = function() {
    if(window.export===1) { return; }
    var skybox = document.getElementById('skybox');
    var id_room = skybox.getAttribute('data-id-room');
    var postObj = {
        type: 'room',
        id: id_room,
        ip_visitor: window.ip_visitor
    };
    var post = JSON.stringify(postObj);
    var url = "../viewer/ajax/set_statistics.php";
    var xhr = new XMLHttpRequest();
    xhr.open('POST', url, true);
    xhr.setRequestHeader('Content-type', 'application/json; charset=UTF-8');
    xhr.send(post);
};

window.set_room_time_statistics = function() {
    if(window.export===1) { return; }
    var skybox = document.getElementById('skybox');
    var id_room = skybox.getAttribute('data-id-room');
    clearInterval(interval_access_time_avg);
    var postObj = {
        type: 'room_time',
        id: id_room,
        access_time_avg: access_time_avg,
        ip_visitor: window.ip_visitor
    };
    var post = JSON.stringify(postObj);
    var url = "../viewer/ajax/set_statistics.php";
    var xhr = new XMLHttpRequest();
    xhr.open('POST', url, true);
    xhr.setRequestHeader('Content-type', 'application/json; charset=UTF-8');
    xhr.send(post);
    access_time_avg = 0;
};

function isFirefoxReality() {
    return /(Mobile VR)/i.test(window.navigator.userAgent);
}
function isOculusBrowser() {
    return /(OculusBrowser)/i.test(window.navigator.userAgent);
}
function isIOS () {
    return /iPad|iPhone|iPod/.test(window.navigator.platform);
}
function isTablet () {
    return /ipad|Nexus (7|9)|xoom|sch-i800|playbook|tablet|kindle/i.test(window.navigator.userAgent);
}
function isR7 () {
    return /R7 Build/.test(window.navigator.userAgent);
}
function isMobileVR () {
    return isOculusBrowser() || isFirefoxReality();
}
var isMobile = (function () {
    var _isMobile = false;
    (function (a) {
        if (/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|mobile.+firefox|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows ce|xda|xiino/i.test(a) || /1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i.test(a.substr(0, 4))) {
            _isMobile = true;
        }
        if (isIOS() || isTablet() || isR7()) {
            _isMobile = true;
        }
        if (isMobileVR()) {
            _isMobile = false;
        }
    })(window.navigator.userAgent || window.navigator.vendor || window.opera);
    return function () { return _isMobile; };
})();

function redirect_to_normal() {
    var url = window.location.href;
    url = url.replace('/vr/','/viewer/');
    window.location.href = url;
}