<?php include 'header.php'?>

<div class="cart-container">
  <table class="cart-table">
    <thead>
      <tr>
        <th>Artikelbezeichnung</th>
        <th>Stückpreis</th>
        <th>Menge</th>
      </tr>
    </thead>
    <tbody>
      <!-- Dummy Artikel 1 -->
      <tr>
        <td>Dummy Artikel 1</td>
        <td>10,00€</td>
        <td>
          <input type="number" class="item-quantity" value="1" min="1">
        </td>
      </tr>
      <!-- Dummy Artikel 2 -->
      <tr>
        <td>Dummy Artikel 2</td>
        <td>20,00€</td>
        <td>
          <input type="number" class="item-quantity" value="1" min="1">
        </td>
      </tr>
    </tbody>
  </table>
  <hr>
  <div class="total-section">
    <span>Gesamtsumme: 30,00€</span>
  </div>
  <button class="order-button">Jetzt bestellen</button>
</div>



<?php include 'footer.php'?>