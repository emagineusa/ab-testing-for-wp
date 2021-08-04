// import handleTestRender from './frontend/handleTestRender';
// import handleTestTracking from './frontend/handleTestTracking';
import ABTest from './frontend/ABTest';

function onLoad(): void {
  // handleTestRender();
  // handleTestTracking();
  const testBlocks = [...document.querySelectorAll('.ABTestWrapper')];
  testBlocks.map((block) => new ABTest(block));
}

document.addEventListener('DOMContentLoaded', onLoad);
