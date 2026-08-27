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
 * TODO describe module payment
 *
 * @module     enrol_authorizedotnet/payment
 * @copyright  2025 YOUR NAME <your@email.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import ajax from "core/ajax";
import Templates from "core/templates";
import Modal from "core/modal";
import ModalEvents from "core/modal_events";
import { getString } from "core/str";
import Notification from "core/notification";

const { call: fetchMany } = ajax;

// Repository function
const getConfigForJs = (instanceid) =>
    fetchMany([{
        methodname: "moodle_authorizedotnet_get_config_for_js",
        args: { instanceid },
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

const switchSdk = (environment) => {
    const sdkUrl = (environment === 'sandbox')
        ? 'https://jstest.authorize.net/v3/AcceptUI.js'
        : 'https://js.authorize.net/v3/AcceptUI.js';

    if (switchSdk.currentlyloaded === sdkUrl) {
        return Promise.resolve();
    }

    if (switchSdk.currentlyloaded) {
        const suspectedScript = document.querySelector(`script[src="${switchSdk.currentlyloaded}"]`);
        if (suspectedScript) {
            suspectedScript.parentNode.removeChild(suspectedScript);
        }
    }

    const script = document.createElement('script');
    return new Promise(resolve => {
        script.onload = () => resolve();
        script.setAttribute('src', sdkUrl);
        script.setAttribute('charset', 'utf-8');
        document.head.appendChild(script);
        switchSdk.currentlyloaded = sdkUrl;
    });
};
switchSdk.currentlyloaded = '';

function authorizeNetPayment(instanceid, userid) {
    const enrolButton = document.getElementById(`enrolbutton-${instanceid}`);
    if (!enrolButton) {
        return;
    }

    enrolButton.addEventListener("click", async () => {
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
            window.responseHandler = function (response) {
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
