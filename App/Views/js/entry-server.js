import app from "./app";
import renderToString from "vue-server-renderer/basic";

renderToString(app, (err, html) => {
  if (err) {
    throw new Error(err);
  }
  dispatch(html);
});