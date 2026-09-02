// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Handles the Authorize.Net Accept.js payment flow on the course enrolment page.
 *
 * @module     enrol_authorizedotnet/payment
 * @copyright  2025 DualCube (https://dualcube.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import ajax from "core/ajax";
import Templates from "core/templates";
import Modal from "core/modal";
import ModalEvents from "core/modal_events";
import {getString} from "core/str";
import Notification from "core/notification";

const {call: fetchMany} = ajax;

// Repository function
const getConfigForJs = (instanceid) =>
    fetchMany([{
        methodname: "moodle_authorizedotnet_get_config_for_js",
        args: {instanceid},
    }])[0];

const processPayment = (instanceid, userid, opaqueData) =>
    fetchMany([{
        methodname: "moodle_authorizedotnet_process_payment",
        args: {
            instanceid,
            userid,
            opaquedata: JSON.stringify(opaqueData),
        },
    }])[0];

/**
 * (Re)loads the Authorize.Net Accept UI SDK.
 *
 * AcceptUI.js wires up its click handling against whichever ".AcceptUI" button
 * exists in the DOM when the script itself runs; it does not pick up buttons
 * added afterwards. Our modal is rebuilt (and its button re-rendered as a new
 * DOM node) on every "Enrol now" click, so the script must be reloaded fresh
 * each time too - reusing a previously loaded copy leaves the new button
 * unwired and silently unresponsive.
 *
 * @param {string} environment "sandbox" or "production".
 * @return {Promise<void>}
 */
const switchSdk = (environment) => {
    const sdkUrl = (environment === 'sandbox')
        ? 'https://jstest.authorize.net/v3/AcceptUI.js'
        : 'https://js.authorize.net/v3/AcceptUI.js';

    document.querySelectorAll('script[data-authorizedotnet-acceptui]').forEach((existing) => {
        existing.parentNode.removeChild(existing);
    });

    const script = document.createElement('script');
    return new Promise(resolve => {
        script.onload = () => resolve();
        script.setAttribute('src', sdkUrl);
        script.setAttribute('charset', 'utf-8');
        script.setAttribute('data-authorizedotnet-acceptui', '1');
        document.head.appendChild(script);
    });
};

/**
 * Wires up the enrol button to launch the Authorize.Net Accept.js payment flow.
 *
 * @param {number} instanceid The enrol instance id.
 * @param {number} userid The current user's id.
 */
function authorizeNetPayment(instanceid, userid) {
    const enrolButton = document.getElementById(`enrolbutton-${instanceid}`);
    if (!enrolButton) {
        return;
    }

    enrolButton.addEventListener("click", async() => {
        let modal;
        try {
            const config = await getConfigForJs(instanceid);
            const body = await Templates.render(
                'enrol_authorizedotnet/authorizedotnet_button',
                {
                    apiloginid: config.apiloginid,
                    clientkey: config.publicclientkey,
                }
            );

            modal = await Modal.create({
                title: getString("pluginname", "enrol_authorizedotnet"),
                body: body,
                show: true,
                removeOnClose: true,
            });

            await switchSdk(config.environment);
            window.responseHandler = function(response) {
                // Prevent outside clicks while processing.
                modal.getRoot().on(ModalEvents.outsideClick, (e) => e.preventDefault());

                if (response.messages.resultCode === "Error") {
                    let errorMessages = '';
                    for (let i = 0; i < response.messages.message.length; i++) {
                        errorMessages += response.messages.message[i].text + '\n';
                    }
                    Notification.alert(getString('error', 'moodle'), errorMessages);
                    modal.hide();
                    return;
                }
                modal.setBody(getString('authorising', 'enrol_authorizedotnet'));

                processPayment(instanceid, userid, response.opaqueData)
                    .then((res) => {
                        modal.hide();
                        if (res.success) {
                            window.location.reload();
                        } else {
                            Notification.alert(getString('error', 'moodle'), res.message);
                        }
                        return undefined;
                    })
                    .catch((err) => {
                        Notification.exception(err);
                        modal.hide();
                    });
            };

        } catch (err) {
            Notification.exception(err);
            if (modal) {
                modal.hide();
            }
        }
    });
}

export default {
    authorizeNetPayment,
};
